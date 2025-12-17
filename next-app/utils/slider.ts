import axios from "./axios";

export type SliderPayload = {
    title: string;
    description?: string;
    link?: string;
    open_in_new_tab?: boolean;
    order?: number;
    status: boolean;
    image?: string;
};

export const fetchSliders = async () => {
    const response = await axios.get("/api/sliders");
    return response.data;
};

export const createSlider = async (data: SliderPayload) => {
    const response = await axios.post("/api/create-sliders", data);
    return response.data;
};

export const updateSlider = async (id: number, data: SliderPayload) => {
    const response = await axios.put(`/api/update-sliders/${id}`, data);
    return response.data;
};

export const deleteSlider = async (id: number) => {
    const response = await axios.delete(`/api/delete-sliders/${id}`);
    return response.data;
};

export const updateSliderOrder = async (orderIds: number[]) => {
    const response = await axios.post("/api/order", { order: orderIds });
    return response.data;
};
