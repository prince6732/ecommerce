"use client";

import React, { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";
import { RiArrowLeftLine } from "react-icons/ri";
import axios from "../../../../utils/axios";

type Category = {
    id: number;
    name: string;
    description?: string;
    secondary_image: string | null;
    link: string | null;
    image: string | null;
};

const imageUrl = `${process.env.NEXT_PUBLIC_UPLOAD_BASE}`;

export default function CategoriesPage() {
    const [categories, setCategories] = useState<Category[]>([]);
    const [loading, setLoading] = useState(true);
    const router = useRouter();
    const pathname = usePathname();

    const fetchCategories = async () => {
        try {
            const res = await axios.get(`${process.env.NEXT_PUBLIC_API_URL}/api/categories`);
            if (Array.isArray(res.data)) {
                setCategories(res.data);
            } else if (res.data.success && Array.isArray(res.data.data)) {
                setCategories(res.data.data);
            }
        } catch (error) {
            console.error("Failed to fetch categories", error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCategories();
    }, []);

    const onDetail = (category: Category) => {
        router.push(`/categories/subcategories/${category.id}`);
    };

    return (
        <section className="py-8 sm:py-12 md:py-16">
            <div className="container mx-auto px-3 sm:px-4 md:px-6">
                {/* Header with Back Button - Hidden on root route */}
                <div className="flex items-center justify-center mb-6 sm:mb-8 md:mb-12 relative">
                    {pathname !== "/" && (
                        <button
                            onClick={() => router.back()}
                            className="absolute left-0 flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 sm:px-4 sm:py-2 bg-white rounded-lg shadow-md hover:shadow-lg transition-all text-gray-700 hover:text-gray-900"
                        >
                            <RiArrowLeftLine className="text-base sm:text-lg" />
                            <span className="text-sm sm:text-base font-medium">Back</span>
                        </button>
                    )}
                    <h2 className="text-xl sm:text-2xl md:text-3xl font-bold text-center">Shop by Category</h2>
                </div>

                {loading ? (
                    <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                        {Array.from({ length: 8 }).map((_, index) => (
                            <div
                                key={index}
                                className="group relative overflow-hidden rounded-lg aspect-[3/4] bg-gray-200 animate-pulse"
                            >
                                <div className="w-full h-full bg-gradient-to-br from-gray-200 via-gray-300 to-gray-200 animate-pulse" />
                                <div className="absolute inset-0 bg-gradient-to-t from-gray-800/60 to-transparent flex items-end p-3 sm:p-4 md:p-6">
                                    <div className="w-full">
                                        <div className="h-4 sm:h-5 md:h-6 bg-white/30 rounded mb-1.5 sm:mb-2 w-3/4 animate-pulse" />
                                        <div className="h-3 sm:h-4 bg-white/20 rounded w-2/3 animate-pulse" />
                                    </div>
                                </div>
                                <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent transform -skew-x-12 animate-shimmer" />
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                        {categories.map((cat) => (
                            <div
                                key={cat.id}
                                onClick={() => onDetail(cat)}
                                className="group relative overflow-hidden rounded-lg aspect-[3/4] block cursor-pointer shadow-sm hover:shadow-xl transition-shadow duration-300"
                            >
                                <img
                                    src={
                                        cat.image
                                            ? `${imageUrl}${cat.image}`
                                            : "https://via.placeholder.com/400x500?text=No+Image"
                                    }
                                    alt={cat.name}
                                    className="w-full h-full object-cover transition-opacity duration-500 group-hover:opacity-0"
                                />
                                {cat.secondary_image && (
                                    <img
                                        src={`${imageUrl}${cat.secondary_image}`}
                                        alt={`${cat.name} secondary`}
                                        className="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                                    />
                                )}
                                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent flex items-end p-3 sm:p-4 md:p-6 transition-all duration-500 group-hover:from-black/80 group-hover:via-black/30">
                                    <div className="w-full">
                                        <h3 className="text-sm sm:text-base md:text-lg lg:text-xl font-semibold text-white mb-0.5 sm:mb-1 line-clamp-2 leading-tight">
                                            {cat.name}
                                        </h3>
                                        <p className="text-white/80 text-xs sm:text-sm opacity-90 group-hover:opacity-100 transition-opacity">
                                            View Collection
                                        </p>
                                    </div>
                                </div>

                                {/* Mobile tap indicator */}
                                <div className="absolute top-2 right-2 sm:top-3 sm:right-3 bg-white/90 backdrop-blur-sm rounded-full p-1.5 sm:p-2 opacity-0 group-hover:opacity-100 transition-opacity sm:block">
                                    <svg className="w-3 h-3 sm:w-4 sm:h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Empty State */}
                {!loading && categories.length === 0 && (
                    <div className="text-center py-12 sm:py-16 md:py-20">
                        <div className="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-full mb-4 sm:mb-6">
                            <svg className="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <h3 className="text-lg sm:text-xl md:text-2xl font-semibold text-gray-900 mb-2">No Categories Found</h3>
                        <p className="text-sm sm:text-base text-gray-600 max-w-md mx-auto px-4">
                            Categories will appear here once they are added to the store.
                        </p>
                    </div>
                )}
            </div>
        </section>
    );
}