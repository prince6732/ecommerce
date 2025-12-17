import axios from "./axios";

export const getOrders = async (params?: {
  status?: string;
  search?: string;
  page?: number;
  per_page?: number;
}) => {
  try {
    const queryParams = new URLSearchParams();
    if (params?.status) queryParams.append('status', params.status);
    if (params?.search) queryParams.append('search', params.search);
    if (params?.page) queryParams.append('page', params.page.toString());
    if (params?.per_page) queryParams.append('per_page', params.per_page.toString());

    const response = await axios.get(`/api/orders?${queryParams.toString()}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching orders:', error);
    throw error;
  }
};

export const getOrder = async (orderId: number) => {
  try {
    const response = await axios.get(`/api/orders/${orderId}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching order:', error);
    throw error;
  }
};

export const placeOrderFromCart = async (orderData: {
  shipping_address: string;
  billing_address?: string;
  notes?: string;
  cart_items?: number[];
}) => {
  try {
    const response = await axios.post(`/api/orders/place-from-cart`, orderData);
    return response.data;
  } catch (error) {
    console.error('Error placing order from cart:', error);
    throw error;
  }
};

export const placeSingleItemOrder = async (orderData: {
  product_id: number;
  variant_id: number;
  quantity: number;
  selected_attributes?: Record<string, string>;
  shipping_address: string;
  billing_address?: string;
  notes?: string;
}) => {
  try {
    const response = await axios.post(`/api/orders/place-single-item`, orderData);
    return response.data;
  } catch (error) {
    console.error('Error placing single item order:', error);
    throw error;
  }
};

export const cancelOrder = async (orderId: number) => {
  try {
    const response = await axios.patch(`/api/orders/${orderId}/cancel`);
    return response.data;
  } catch (error) {
    console.error('Error cancelling order:', error);
    throw error;
  }
};

export const getOrderTracking = async (orderId: number) => {
  try {
    const response = await axios.get(`/api/orders/${orderId}/tracking`);
    return response.data;
  } catch (error) {
    console.error('Error fetching order tracking:', error);
    throw error;
  }
};

export const getAdminOrders = async (params?: {
  status?: string;
  search?: string;
  from_date?: string;
  to_date?: string;
  page?: number;
  per_page?: number;
}) => {
  try {
    const queryParams = new URLSearchParams();
    if (params?.status) queryParams.append('status', params.status);
    if (params?.search) queryParams.append('search', params.search);
    if (params?.from_date) queryParams.append('from_date', params.from_date);
    if (params?.to_date) queryParams.append('to_date', params.to_date);
    if (params?.page) queryParams.append('page', params.page.toString());
    if (params?.per_page) queryParams.append('per_page', params.per_page.toString());

    const response = await axios.get(`/api/admin/orders?${queryParams.toString()}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching admin orders:', error);
    throw error;
  }
};

export const getAdminOrder = async (orderId: number) => {
  try {
    const response = await axios.get(`/api/admin/orders/${orderId}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching admin order:', error);
    throw error;
  }
};

export const updateOrderStatus = async (orderId: number, statusData: {
  status: string;
  description: string;
  location?: string;
}) => {
  try {
    const response = await axios.patch(`/api/admin/orders/${orderId}/status`, statusData);
    return response.data;
  } catch (error) {
    console.error('Error updating order status:', error);
    throw error;
  }
};

export const getOrderStats = async () => {
  try {
    const response = await axios.get(`/api/admin/orders/stats`);
    return response.data;
  } catch (error) {
    console.error('Error fetching order stats:', error);
    throw error;
  }
};

export const getCompletedOrders = async (params?: {
  search?: string;
  from_date?: string;
  to_date?: string;
  page?: number;
  per_page?: number;
}) => {
  try {
    const queryParams = new URLSearchParams();
    if (params?.search) queryParams.append('search', params.search);
    if (params?.from_date) queryParams.append('from_date', params.from_date);
    if (params?.to_date) queryParams.append('to_date', params.to_date);
    if (params?.page) queryParams.append('page', params.page.toString());
    if (params?.per_page) queryParams.append('per_page', params.per_page.toString());

    const response = await axios.get(`/api/admin/orders/completed?${queryParams.toString()}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching completed orders:', error);
    throw error;
  }
};

export const getCompletedOrderDetails = async (orderId: number) => {
  try {
    const response = await axios.get(`/api/admin/orders/completed/${orderId}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching completed order details:', error);
    throw error;
  }
};

export const formatOrderStatus = (status: string): string => {
  return status.charAt(0).toUpperCase() + status.slice(1);
};

export const getOrderStatusColor = (status: string): string => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    case 'confirmed':
      return 'bg-blue-100 text-blue-800 border-blue-200';
    case 'processing':
      return 'bg-purple-100 text-purple-800 border-purple-200';
    case 'shipped':
      return 'bg-orange-100 text-orange-800 border-orange-200';
    case 'delivered':
      return 'bg-green-100 text-green-800 border-green-200';
    case 'completed':
      return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    case 'cancelled':
      return 'bg-red-100 text-red-800 border-red-200';
    default:
      return 'bg-gray-100 text-gray-800 border-gray-200';
  }
};

export const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR'
  }).format(amount);
};

export const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-IN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

export const ORDER_STATUSES = {
  PENDING: 'pending',
  CONFIRMED: 'confirmed',
  PROCESSING: 'processing',
  SHIPPED: 'shipped',
  DELIVERED: 'delivered',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled'
} as const;

export const PAYMENT_METHODS = {
  CASH_ON_DELIVERY: 'cash_on_delivery',
  ONLINE: 'online'
} as const;

export const PAYMENT_STATUSES = {
  PENDING: 'pending',
  PAID: 'paid',
  FAILED: 'failed'
} as const;

export interface OrderItem {
  id: number;
  quantity: number;
  price: number;
  total: number;
  selected_attributes: Record<string, string> | null;
  product: {
    id: number;
    name: string;
    image_url: string | null;
  };
  variant: {
    id: number;
    title: string;
    sku: string;
    image_url: string | null;
  };
}

export interface TrackingRecord {
  id: number;
  status: string;
  description: string;
  location: string | null;
  tracked_at: string;
}

export interface Order {
  id: number;
  order_number: string;
  status: string;
  payment_method: string;
  payment_status: string;
  subtotal: number;
  shipping_fee: number;
  tax: number;
  total: number;
  shipping_address: string;
  billing_address: string | null;
  notes: string | null;
  created_at: string;
  order_items: OrderItem[];
  tracking_records: TrackingRecord[];
}

export interface OrderStats {
  total_orders: number;
  pending_orders: number;
  confirmed_orders: number;
  processing_orders: number;
  shipped_orders: number;
  delivered_orders: number;
  cancelled_orders: number;
  total_revenue: number;
  todays_orders: number;
  this_month_orders: number;
}