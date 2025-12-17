"use client";

import { useState, useEffect } from "react";
import {
    FaStar,
    FaStarHalfAlt,
    FaUser,
    FaChevronLeft,
    FaChevronRight,
} from "react-icons/fa";

const testimonials = [
    {
        id: 1,
        name: "Emily Richardson",
        role: "Loyal Customer",
        text: "The quality of the clothes is exceptional. I've ordered multiple times and have never been disappointed. The customer service is also top-notch!",
        rating: 5,
    },
    {
        id: 2,
        name: "Michael Thompson",
        role: "Verified Buyer",
        text: "Fast shipping and the products look exactly like the pictures. The sizing guide was very helpful. Will definitely shop here again!",
        rating: 4.5,
    },
    {
        id: 3,
        name: "Sophia Martinez",
        role: "Repeat Customer",
        text: "I love the sustainable approach this brand takes. The packaging is eco-friendly and the clothes are made from high-quality, sustainable materials.",
        rating: 5,
    },
    {
        id: 4,
        name: "Daniel Lewis",
        role: "Happy Shopper",
        text: "Great variety and top-notch material. Every product I ordered matched my expectations perfectly. I’ll surely recommend it to my friends.",
        rating: 5,
    },
    {
        id: 5,
        name: "Ava Johnson",
        role: "New Customer",
        text: "User-friendly website and fast delivery. I’m impressed with the customer support team as well.",
        rating: 4,
    },
];

const Testimonials = () => {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [itemsPerSlide, setItemsPerSlide] = useState(3);

    // ✅ Detect screen width change
    useEffect(() => {
        const handleResize = () => {
            setItemsPerSlide(window.innerWidth < 768 ? 1 : 3);
        };
        handleResize();
        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, []);

    // ✅ Auto Slide (continuous)
    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentIndex((prevIndex) =>
                prevIndex + itemsPerSlide >= testimonials.length ? 0 : prevIndex + itemsPerSlide
            );
        }, 3000);

        return () => clearInterval(interval);
    }, [itemsPerSlide]); // <-- changed dependency for smooth looping

    const nextSlide = () => {
        setCurrentIndex((prevIndex) =>
            prevIndex + itemsPerSlide >= testimonials.length ? 0 : prevIndex + itemsPerSlide
        );
    };

    const prevSlide = () => {
        setCurrentIndex((prevIndex) =>
            prevIndex - itemsPerSlide < 0
                ? Math.max(testimonials.length - itemsPerSlide, 0)
                : prevIndex - itemsPerSlide
        );
    };

    // ✅ Visible testimonials with loop wrap
    const visibleTestimonials = testimonials.slice(
        currentIndex,
        currentIndex + itemsPerSlide
    );
    if (visibleTestimonials.length < itemsPerSlide) {
        visibleTestimonials.push(
            ...testimonials.slice(0, itemsPerSlide - visibleTestimonials.length)
        );
    }

    return (
        <section className="py-16 bg-gray-50 px-4 relative overflow-hidden">
            <div className="container mx-auto px-4">
                <h2 className="text-3xl font-bold text-center mb-12">
                    What Our Customers Say
                </h2>

                <div className="flex justify-center items-center gap-4">
                    {/* Left Arrow */}
                    <button
                        onClick={prevSlide}
                        className="p-3 bg-gray-200 rounded-full hover:bg-gray-300 transition"
                    >
                        <FaChevronLeft />
                    </button>

                    {/* Testimonials Grid */}
                    <div
                        className={`grid gap-6 w-full max-w-full transition-all duration-700 ease-in-out ${itemsPerSlide === 1 ? "grid-cols-1" : "md:grid-cols-3"
                            }`}
                    >
                        {visibleTestimonials.map((t, i) => (
                            <div
                                key={i}
                                className="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-all duration-300"
                            >
                                <div className="flex text-amber-400 mb-4">
                                    {Array.from({ length: Math.floor(t.rating) }).map((_, j) => (
                                        <FaStar key={j} />
                                    ))}
                                    {t.rating % 1 !== 0 && <FaStarHalfAlt />}
                                </div>

                                <p className="text-gray-700 mb-6 italic">"{t.text}"</p>

                                <div className="flex items-center">
                                    <div className="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 mr-4">
                                        <FaUser className="text-xl" />
                                    </div>
                                    <div>
                                        <h4 className="font-medium text-gray-900">{t.name}</h4>
                                        <p className="text-sm text-gray-500">{t.role}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Right Arrow */}
                    <button
                        onClick={nextSlide}
                        className="p-3 bg-gray-200 rounded-full hover:bg-gray-300 transition"
                    >
                        <FaChevronRight />
                    </button>
                </div>
            </div>
        </section>
    );
};

export default Testimonials;
