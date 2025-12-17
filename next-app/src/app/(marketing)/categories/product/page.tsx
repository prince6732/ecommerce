"use client";

import React, { Suspense } from "react";
import { useSearchParams } from "next/navigation";
import ProductShowComponent from "@/components/ProductShow";

function ProductShowContent() {
    const searchParams = useSearchParams();
    const subcategoryId = searchParams.get('subcategoryId') ? Number(searchParams.get('subcategoryId')) : null;

    if (!subcategoryId) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <div className="text-center">
                    <h2 className="text-2xl font-bold text-gray-900 mb-4">No Subcategory Selected</h2>
                    <p className="text-gray-600">Please select a subcategory to view products.</p>
                </div>
            </div>
        );
    }

    return <ProductShowComponent subcategoryId={subcategoryId} />;
}

export default function ProductShow() {
    return (
        <Suspense fallback={
            <div className="min-h-screen flex items-center justify-center">
                <div className="text-center">
                    <div className="w-16 h-16 border-4 border-orange-200 border-t-orange-500 rounded-full animate-spin mx-auto mb-4"></div>
                    <p className="text-gray-600">Loading...</p>
                </div>
            </div>
        }>
            <ProductShowContent />
        </Suspense>
    );
}
