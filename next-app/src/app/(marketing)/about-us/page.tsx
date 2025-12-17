'use client';

import {
    Store,
    Users,
    ShieldCheck,
    Truck,
    Heart,
    Award,
    Target,
    Sparkles,
    Zap,
    TrendingUp,
    Globe,
    ArrowRight
} from 'lucide-react';
import Link from 'next/link';

export default function AboutUsPage() {
    return (
        <div className="min-h-screen bg-gradient-to-b from-gray-50 via-orange-50/30 to-white">
            {/* Hero Section */}
            <section className="relative bg-gradient-to-r from-orange-500 to-yellow-500 text-white py-20 overflow-hidden">
                <div className="absolute inset-0 bg-black/10"></div>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="text-center">
                        <div className="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl mb-6">
                            <Store className="w-10 h-10" />
                        </div>
                        <h1 className="text-4xl sm:text-5xl md:text-6xl font-bold mb-6">About E-Com Array</h1>
                        <p className="text-lg sm:text-xl text-white/90 max-w-3xl mx-auto">
                            Your trusted destination for quality products, exceptional service, and unforgettable shopping experiences.
                        </p>
                    </div>
                </div>
            </section>

            {/* Mission & Vision */}
            <section className="py-20 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="grid md:grid-cols-2 gap-8">
                        <div className="bg-gradient-to-br from-white to-orange-50 rounded-3xl shadow-2xl p-8 md:p-10 border border-orange-100 hover:shadow-3xl transition-all duration-300 transform hover:-translate-y-1">
                            <div className="flex items-center gap-4 mb-6">
                                <div className="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <Target className="w-8 h-8 text-white" />
                                </div>
                                <h2 className="text-3xl font-bold text-gray-900">Our Mission</h2>
                            </div>
                            <p className="text-gray-700 leading-relaxed text-lg">
                                To revolutionize online shopping by providing an exceptional platform that connects customers
                                with high-quality products, backed by outstanding customer service and innovative technology.
                                We strive to make every shopping experience seamless, enjoyable, and memorable.
                            </p>
                        </div>

                        <div className="bg-gradient-to-br from-white to-yellow-50 rounded-3xl shadow-2xl p-8 md:p-10 border border-yellow-100 hover:shadow-3xl transition-all duration-300 transform hover:-translate-y-1">
                            <div className="flex items-center gap-4 mb-6">
                                <div className="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <Sparkles className="w-8 h-8 text-white" />
                                </div>
                                <h2 className="text-3xl font-bold text-gray-900">Our Vision</h2>
                            </div>
                            <p className="text-gray-700 leading-relaxed text-lg">
                                To become the leading e-commerce platform recognized for trust, innovation, and customer
                                satisfaction. We envision a future where online shopping is not just convenient but also
                                personalized, sustainable, and accessible to everyone.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* Stats Section */}
            <section className="py-20 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="bg-gradient-to-br from-orange-500 via-orange-600 to-yellow-500 rounded-3xl shadow-2xl overflow-hidden">
                        <div className="grid grid-cols-2 md:grid-cols-4 divide-x divide-white/20">
                            {[
                                { number: '50K+', label: 'Happy Customers', icon: Users },
                                { number: '10K+', label: 'Products', icon: Store },
                                { number: '98%', label: 'Satisfaction Rate', icon: Heart },
                                { number: '24/7', label: 'Support', icon: ShieldCheck },
                            ].map((stat, index) => (
                                <div key={index} className="p-8 md:p-10 text-center text-white hover:bg-white/10 transition-all duration-300 group">
                                    <div className="flex justify-center mb-4">
                                        <div className="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                            <stat.icon className="w-8 h-8" />
                                        </div>
                                    </div>
                                    <div className="text-4xl md:text-5xl font-bold mb-2 tracking-tight">{stat.number}</div>
                                    <div className="text-sm md:text-base text-white/90 font-medium">{stat.label}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* Why Choose Us */}
            <section className="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white to-orange-50/50">
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-16">
                        <div className="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-2xl mb-4 shadow-lg">
                            <Award className="w-8 h-8 text-white" />
                        </div>
                        <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Why Choose E-Com Array?</h2>
                        <p className="text-gray-600 text-lg max-w-2xl mx-auto">
                            We're committed to providing the best shopping experience with features that matter most to you.
                        </p>
                    </div>

                    <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        {[
                            {
                                icon: ShieldCheck,
                                title: 'Secure Shopping',
                                description: 'Your data is protected with industry-standard encryption and secure payment gateways.',
                                gradient: 'from-orange-500 to-orange-600'
                            },
                            {
                                icon: Truck,
                                title: 'Fast Delivery',
                                description: 'Quick and reliable shipping to get your products to you as soon as possible.',
                                gradient: 'from-yellow-500 to-yellow-600'
                            },
                            {
                                icon: Award,
                                title: 'Quality Products',
                                description: 'Every product is carefully vetted to ensure it meets our high quality standards.',
                                gradient: 'from-orange-500 to-yellow-500'
                            },
                            {
                                icon: Heart,
                                title: 'Customer First',
                                description: 'Our dedicated support team is always here to help with any questions or concerns.',
                                gradient: 'from-pink-500 to-orange-500'
                            },
                            {
                                icon: Store,
                                title: 'Wide Selection',
                                description: 'Thousands of products across multiple categories to choose from.',
                                gradient: 'from-orange-600 to-orange-700'
                            },
                            {
                                icon: Users,
                                title: 'Trusted Community',
                                description: 'Join thousands of satisfied customers who trust us for their shopping needs.',
                                gradient: 'from-yellow-600 to-orange-600'
                            },
                        ].map((feature, index) => (
                            <div
                                key={index}
                                className="bg-white rounded-3xl shadow-xl p-8 hover:shadow-2xl transition-all duration-300 border border-gray-100 group transform hover:-translate-y-2"
                            >
                                <div className={`w-16 h-16 bg-gradient-to-br ${feature.gradient} rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg`}>
                                    <feature.icon className="w-8 h-8 text-white" />
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-3">{feature.title}</h3>
                                <p className="text-gray-600 leading-relaxed">{feature.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Our Story */}
            <section className="py-20 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="bg-gradient-to-br from-white to-orange-50 rounded-3xl shadow-2xl overflow-hidden">
                        <div className="grid md:grid-cols-2 gap-0">
                            <div className="p-8 md:p-12 lg:p-16 flex flex-col justify-center">
                                <div className="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-2xl mb-6 shadow-lg">
                                    <Zap className="w-7 h-7 text-white" />
                                </div>
                                <h2 className="text-4xl font-bold text-gray-900 mb-6">Our Story</h2>
                                <p className="text-gray-700 mb-5 text-lg leading-relaxed">
                                    E-Com Array was founded with a simple yet powerful vision: to create an online shopping destination that
                                    puts customers first while offering an extensive range of quality products at competitive prices.
                                </p>
                                <p className="text-gray-700 mb-5 text-lg leading-relaxed">
                                    From our humble beginnings to becoming a trusted e-commerce platform, we've grown alongside our
                                    customers, constantly evolving to meet their needs and exceed their expectations.
                                </p>
                                <p className="text-gray-700 text-lg leading-relaxed">
                                    Today, we serve thousands of happy customers, offering carefully curated products across multiple
                                    categories with a commitment to quality, affordability, and exceptional service.
                                </p>

                                <div className="flex items-center gap-4 mt-8">
                                    <div className="flex -space-x-2">
                                        <div className="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 border-2 border-white"></div>
                                        <div className="w-10 h-10 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 border-2 border-white"></div>
                                        <div className="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-yellow-500 border-2 border-white"></div>
                                    </div>
                                    <div className="text-sm text-gray-600 font-medium">
                                        Trusted by 50,000+ customers
                                    </div>
                                </div>
                            </div>
                            <div className="relative h-64 md:h-auto min-h-[400px]">
                                <img
                                    src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&auto=format&fit=crop&q=80"
                                    alt="Modern Shopping Experience"
                                    className="absolute inset-0 w-full h-full object-cover"
                                />
                                <div className="absolute inset-0 bg-gradient-to-br from-orange-500/20 to-yellow-500/20"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="py-20 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="relative overflow-hidden rounded-3xl shadow-2xl">
                        <div className="absolute inset-0 bg-gradient-to-br from-orange-600 via-orange-500 to-yellow-500"></div>
                        <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48cGF0aCBkPSJNMzYgMzRjMC0yLjIxLTEuNzktNC00LTRzLTQgMS43OS00IDQgMS43OSA0IDQgNCA0LTEuNzkgNC00em0wLTEwYzAtMi4yMS0xLjc5LTQtNC00cy00IDEuNzktNCA0IDEuNzkgNCA0IDQgNC0xLjc5IDQtNHptMC0xMGMwLTIuMjEtMS43OS00LTQtNHMtNCAxLjc5LTQgNCAxLjc5IDQgNCA0IDQtMS43OSA0LTR6TTEyIDM0YzAtMi4yMS0xLjc5LTQtNC00cy00IDEuNzktNCA0IDEuNzkgNCA0IDQgNC0xLjc5IDQtNHptMC0xMGMwLTIuMjEtMS43OS00LTQtNHMtNCAxLjc5LTQgNCAxLjc5IDQgNCA0IDQtMS43OSA0LTR6bTAtMTBjMC0yLjIxLTEuNzktNC00LTRzLTQgMS43OS00IDQgMS43OSA0IDQgNCA0LTEuNzkgNC00ek0yNCAzNGMwLTIuMjEtMS43OS00LTQtNHMtNCAxLjc5LTQgNCAxLjc5IDQgNCA0IDQtMS43OSA0LTR6bTAtMTBjMC0yLjIxLTEuNzktNC00LTRzLTQgMS43OS00IDQgMS43OSA0IDQgNCA0LTEuNzkgNC00em0wLTEwYzAtMi4yMS0xLjc5LTQtNC00cy00IDEuNzktNCA0IDEuNzkgNCA0IDQgNC0xLjc5IDQtNHoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-20"></div>

                        <div className="relative p-8 md:p-16 text-center text-white">
                            <div className="inline-flex items-center justify-center w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl mb-6 shadow-lg">
                                <TrendingUp className="w-8 h-8" />
                            </div>
                            <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold mb-6">Ready to Start Shopping?</h2>
                            <p className="text-lg md:text-xl text-white/90 mb-10 max-w-2xl mx-auto">
                                Join thousands of satisfied customers and discover amazing products at unbeatable prices today.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                <Link
                                    href="/products"
                                    className="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-orange-600 font-bold rounded-2xl hover:bg-gray-50 transition-all shadow-2xl hover:shadow-3xl transform hover:-translate-y-1"
                                >
                                    Browse Products
                                    <ArrowRight className="w-5 h-5" />
                                </Link>
                                <Link
                                    href="/contact-us"
                                    className="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-md text-white font-bold rounded-2xl hover:bg-white/20 transition-all border-2 border-white/30 hover:border-white/50"
                                >
                                    Contact Us
                                    <Globe className="w-5 h-5" />
                                </Link>
                            </div>

                            {/* Trust indicators */}
                            <div className="flex flex-wrap justify-center gap-8 mt-12 pt-8 border-t border-white/20">
                                <div className="text-center">
                                    <div className="text-2xl font-bold mb-1">50K+</div>
                                    <div className="text-sm text-white/80">Active Users</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold mb-1">4.9/5</div>
                                    <div className="text-sm text-white/80">Rating</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold mb-1">100%</div>
                                    <div className="text-sm text-white/80">Secure</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    );
}
