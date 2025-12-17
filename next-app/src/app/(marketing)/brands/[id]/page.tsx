"use client";

import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import {
    ArrowLeft,
    Package,
    Loader2,
    ShoppingBag,
    Heart,
    Award,
    CheckCircle,
    ChevronDown
} from "lucide-react";
import { getBrandById } from "../../../../../utils/brand";
import { Brand } from "@/common/interface";
import { useLike } from "@/context/LikeContext";
import imgPlaceholder from "@/public/imagePlaceholder.png";
import axios from "../../../../../utils/axios";

const basePath = process.env.NEXT_PUBLIC_UPLOAD_BASE;

interface BrandProduct {
    id: number;
    name: string;
    description: string;
    item_code?: string;
    image_url: string | null;
    category: { id: number; name: string } | null;
    brand: { id: number; name: string } | null;
    min_price: number;
    max_price: number;
    total_stock: number;
    variants_count: number;
}

interface ExtendedBrand extends Brand {
    products_count?: number;
}

export default function BrandDetailPage() {
    const params = useParams();
    const router = useRouter();
    const brandId = params.id as string;
    const { likedProducts, toggleLike } = useLike();
    const [brand, setBrand] = useState<ExtendedBrand | null>(null);
    const [products, setProducts] = useState<BrandProduct[]>([]);
    const [loading, setLoading] = useState(true);
    const [loadingMore, setLoadingMore] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [hasMore, setHasMore] = useState(false);

    useEffect(() => {
        const fetchBrandDetails = async () => {
            try {
                setLoading(true);
                const data = await getBrandById(brandId);
                setBrand(data);

                // Fetch first page of products
                await fetchProducts(1);
            } catch (err: any) {
                console.error("Error fetching brand:", err);
                setError(err.message || "Failed to load brand details");
            } finally {
                setLoading(false);
            }
        };

        if (brandId) {
            fetchBrandDetails();
        }
    }, [brandId]);

    const fetchProducts = async (page: number) => {
        try {
            if (page > 1) {
                setLoadingMore(true);
            }

            const response = await axios.get(`/api/brands/${brandId}/products`, {
                params: {
                    page,
                    per_page: 8,
                },
            });

            const { products: newProducts, has_more } = response.data;

            if (page === 1) {
                setProducts(newProducts);
            } else {
                setProducts((prev) => [...prev, ...newProducts]);
            }

            setHasMore(has_more);
            setCurrentPage(page);
        } catch (err: any) {
            console.error("Error fetching products:", err);
        } finally {
            setLoadingMore(false);
        }
    };

    const handleLoadMore = () => {
        if (!loadingMore && hasMore) {
            fetchProducts(currentPage + 1);
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-gray-50 to-white flex items-center justify-center">
                <div className="text-center">
                    <Loader2 className="w-12 h-12 text-orange-500 animate-spin mx-auto mb-4" />
                    <p className="text-gray-600">Loading brand details...</p>
                </div>
            </div>
        );
    }

    if (error || !brand) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <div className="text-center">
                    <Package className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h1 className="text-2xl font-bold text-gray-900 mb-2">Brand Not Found</h1>
                    <p className="text-gray-600 mb-6">{error || "The brand you're looking for doesn't exist."}</p>
                    <button
                        onClick={() => router.push("/")}
                        className="px-6 py-3 bg-gradient-to-r from-orange-500 to-yellow-500 text-white rounded-xl hover:from-orange-600 hover:to-yellow-600 transition-all duration-300 font-medium"
                    >
                        Go to Home
                    </button>
                </div>
            </div>
        );
    }

    const sections = [
        {
            image: brand.image1,
            description: brand.description1,
            layout: "image-left",
        },
        {
            image: brand.image2,
            description: brand.description2,
            layout: "image-right",
        },
        {
            image: brand.image3,
            description: brand.description3,
            layout: "image-left",
        },
    ];

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-orange-50/20 to-white">
            {/* Hero Header with Background */}
            <div className="relative bg-gradient-to-r from-orange-500 to-yellow-400 text-white">
                <div className="container mx-auto px-4 py-10 md:py-14 relative z-10">

                    {/* Back Button */}
                    <button
                        onClick={() => router.back()}
                        className="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition mb-6 text-sm"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Back
                    </button>

                    {/* Content Row */}
                    <div className="flex flex-col md:flex-row items-center gap-10">

                        {/* Brand Info */}
                        <div className="flex-1">
                            <h1 className="text-3xl md:text-4xl font-bold mb-4">{brand.name}</h1>

                            <div
                                className="text-white/90 leading-relaxed max-w-2xl text-base md:text-lg"
                                dangerouslySetInnerHTML={{ __html: brand.description ?? "" }}
                            />

                            {/* Stats */}
                            <div className="flex flex-wrap gap-5 mt-6 text-white/90">
                                <div className="flex items-center gap-2">
                                    <ShoppingBag className="w-5 h-5" />
                                    <span>{brand.products_count || 0} Products</span>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Award className="w-5 h-5" />
                                    <span>Trusted Brand</span>
                                </div>

                                <div className="flex items-center gap-2">
                                    <CheckCircle className="w-5 h-5" />
                                    <span>Quality Assured</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div className="container mx-auto px-4 py-12">
                {/* Brand Story Sections */}
                <div className="space-y-16 mb-16">
                    {sections.map((section, index) => {
                        if (!section.image || !section.description) return null;

                        const imageUrl = `${basePath}${section.image}`;

                        return (
                            <div
                                key={index}
                                className={`flex flex-col ${section.layout === "image-right"
                                    ? "lg:flex-row-reverse"
                                    : "lg:flex-row"
                                    } gap-8 lg:gap-12 items-center`}
                            >
                                {/* Image */}
                                <div className="w-full lg:w-1/2">
                                    <div className="relative rounded-2xl overflow-hidden shadow-2xl group">
                                        <div className="aspect-[4/3] relative">
                                            <Image
                                                src={imageUrl}
                                                alt={`${brand.name} - Section ${index + 1}`}
                                                fill
                                                className="object-cover group-hover:scale-105 transition-transform duration-500"
                                                unoptimized
                                            />
                                        </div>
                                        <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    </div>
                                </div>

                                {/* Description */}
                                <div className="w-full lg:w-1/2">
                                    <div className="space-y-6">
                                        <div className="prose prose-lg max-w-none">
                                            <div
                                                className="text-gray-700 leading-relaxed space-y-4"
                                                dangerouslySetInnerHTML={{ __html: section.description }}
                                            />
                                        </div>

                                        {/* Decorative element */}
                                        <div className="flex items-center gap-2 pt-4">
                                            <div className="w-2 h-2 bg-orange-500 rounded-full"></div>
                                            <div className="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                            <div className="w-2 h-2 bg-orange-500 rounded-full"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}

                    {sections.every((s) => !s.image || !s.description) && (
                        <div className="text-center py-16">
                            <Package className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">
                                No additional information available
                            </h3>
                            <p className="text-gray-600">Brand details will be updated soon.</p>
                        </div>
                    )}
                </div>

                {/* Products Section */}
                {products.length > 0 ? (
                    <>
                        {/* Products Header */}
                        <div className="mb-8 text-center">
                            <h2 className="text-3xl font-bold text-gray-900 mb-2">
                                Our Products
                            </h2>
                            <p className="text-gray-600">
                                Explore {brand.products_count} amazing products from {brand.name}
                            </p>
                        </div>

                        {/* Products Grid */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            {products.map((product) => {
                                const isLiked = likedProducts.includes(product.id);

                                return (
                                    <div
                                        key={product.id}
                                        className="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group"
                                    >
                                        <Link href={`/products/${product.id}`}>
                                            {/* Product Image */}
                                            <div className="relative h-64 bg-gray-100 overflow-hidden">
                                                <Image
                                                    src={
                                                        product.image_url
                                                            ? `${basePath}${product.image_url}`
                                                            : imgPlaceholder
                                                    }
                                                    alt={product.name}
                                                    fill
                                                    unoptimized
                                                    className="object-cover group-hover:scale-110 transition-transform duration-300"
                                                />

                                                {/* Stock Badge */}
                                                {product.total_stock > 0 ? (
                                                    <div className="absolute top-3 left-3 px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
                                                        In Stock
                                                    </div>
                                                ) : (
                                                    <div className="absolute top-3 left-3 px-3 py-1 bg-red-500 text-white text-xs font-semibold rounded-full">
                                                        Out of Stock
                                                    </div>
                                                )}
                                            </div>
                                        </Link>

                                        {/* Product Info */}
                                        <div className="p-4">
                                            <Link href={`/products/${product.id}`}>
                                                <h3 className="font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-orange-600 transition-colors min-h-[48px]">
                                                    {product.name}
                                                </h3>

                                                {product.category && (
                                                    <p className="text-sm text-gray-500 mb-3">
                                                        {product.category.name}
                                                    </p>
                                                )}

                                            </Link>

                                            {/* Action Buttons */}
                                            <div className="flex gap-2">
                                                <Link
                                                    href={`/products/${product.id}`}
                                                    className="flex-1 px-4 py-2 bg-gradient-to-r from-orange-500 to-yellow-500 text-white rounded-xl hover:from-orange-600 hover:to-yellow-600 transition-all duration-300 font-semibold text-center"
                                                >
                                                    View Details
                                                </Link>
                                                <button
                                                    onClick={() => toggleLike(product.id)}
                                                    className={`px-4 py-2 rounded-xl transition-all duration-300 ${isLiked
                                                        ? 'bg-red-500 text-white hover:bg-red-600'
                                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                                        }`}
                                                >
                                                    <Heart
                                                        className={`w-5 h-5 ${isLiked ? 'fill-current' : ''}`}
                                                    />
                                                </button>
                                            </div>

                                            {/* Variants Info */}
                                            {product.variants_count > 1 && (
                                                <p className="text-xs text-gray-500 mt-2 text-center">
                                                    {product.variants_count} variants available
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Load More Button */}
                        {hasMore && (
                            <div className="mt-12 text-center">
                                <button
                                    onClick={handleLoadMore}
                                    disabled={loadingMore}
                                    className="px-8 py-4 bg-gradient-to-r from-orange-500 to-yellow-500 text-white rounded-xl hover:from-orange-600 hover:to-yellow-600 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                                >
                                    {loadingMore ? (
                                        <>
                                            <Loader2 className="w-5 h-5 animate-spin" />
                                            Loading...
                                        </>
                                    ) : (
                                        <>
                                            Load More Products
                                            <ChevronDown className="w-5 h-5" />
                                        </>
                                    )}
                                </button>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="text-center py-16">
                        <ShoppingBag className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-xl font-semibold text-gray-900 mb-2">
                            No products available
                        </h3>
                        <p className="text-gray-600 mb-6">
                            Products from this brand will be added soon.
                        </p>
                    </div>
                )}
            </div>

            {/* Bottom CTA Section */}
            <div className="bg-gradient-to-r from-orange-500 to-yellow-500 py-12">
                <div className="container mx-auto px-4 text-center text-white">
                    <h2 className="text-3xl font-bold mb-4">Discover More Brands</h2>
                    <p className="text-white/90 mb-6 max-w-2xl mx-auto">
                        Explore our complete collection of trusted brands and find products that match your style.
                    </p>
                    <button
                        onClick={() => router.push('/brands')}
                        className="px-8 py-4 bg-white text-orange-600 rounded-xl hover:bg-gray-100 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                    >
                        View All Brands
                    </button>
                </div>
            </div>
        </div>
    );
}
