import { Category } from "@/common/interface";
import axios from "./axios";

export const getCategories = async () => {
    const response = await axios.get("/api/categories");
    return response.data;
};

export const getCategoryById = async (id: number) => {
    const response = await axios.get(`/api/get-category/${id}`);
    return response.data;
};

export const getCategoryByIdForProduct = async (id: string) => {
    const response = await axios.get(`/api/get-category-for-product/${id}`);
    return response.data;
};

export const createCategory = async (data: FormData) => {
    const response = await axios.post<Category>(`/api/create-categories`, data);
    return response.data;
};

export const updateCategory = async (id: string, data: FormData) => {
    const response = await axios.put<Category>(`/api/update-category/${id}`, data);
    return response.data;
};

export const deleteCategory = async (id: string) => {
    const response = await axios.delete(`/api/delete-category/${id}`);
    return response.data;
};
