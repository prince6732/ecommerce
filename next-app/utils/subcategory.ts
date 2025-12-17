import { Category } from "@/common/interface";
import axios from "./axios";

export const getSubcategories = async (parentId: string) => {
    const response = await axios.get(`/api/subcategories?parent_id=${parentId}`);
    return response.data;
};

export const createSubcategory = async (data: FormData) => {
    const response = await axios.post<Category>("/api/create-subcategory", data);
    return response.data;
};

export const updateSubcategory = async (id: string, data: FormData) => {
    const response = await axios.put<Category>(`/api/update-subcategory/${id}`, data);
    return response.data;
};

export const deleteSubcategory = async (id: string) => {
    const response = await axios.delete(`/api/delete-subcategory/${id}`);
    return response.data;
};
