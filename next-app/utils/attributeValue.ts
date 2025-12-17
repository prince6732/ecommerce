import { AttributeValuePayload } from "@/common/interface";
import axios from "./axios";

export const fetchAttributeValues = async (attributeId: number) => {
    const response = await axios.get(`/api/attribute-values?attribute_id=${attributeId}`);
    return response.data;
};

export const createAttributeValue = async (data: AttributeValuePayload) => {
    const payload = { ...data, description: data.description ?? undefined, status: Boolean(data.status) };
    const response = await axios.post("/api/create-attribute-value", payload);
    return response.data;
};

export const updateAttributeValue = async (id: number, data: AttributeValuePayload) => {
    const payload = { ...data, description: data.description ?? undefined, status: Boolean(data.status) };
    const response = await axios.put(`/api/update-attribute-value/${id}`, payload);
    return response.data;
};

export const deleteAttributeValue = async (id: number) => {
    const response = await axios.delete(`/api/delete-attribute-value/${id}`);
    return response.data;
};
