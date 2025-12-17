import axios from "./axios";

export const fetchAttributes = async () => {
    const response = await axios.get("/api/attributes");
    return response.data;
};

export const createAttribute = async (data: { name: string; description?: string; status: boolean }) => {
    const payload = { ...data, description: data.description ?? undefined };
    const response = await axios.post("/api/create-attribute", payload);
    return response.data;
};

export const updateAttribute = async (id: number, data: { name: string; description?: string; status: boolean }) => {
    const payload = { ...data, description: data.description ?? undefined };
    const response = await axios.put(`/api/update-attribute/${id}`, payload);
    return response.data;
};

export const deleteAttribute = async (id: number) => {
    const response = await axios.delete(`/api/delete-attribute/${id}`);
    return response.data;
};
