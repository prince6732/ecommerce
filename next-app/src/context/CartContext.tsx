"use client";

import { createContext, useContext, useState, useEffect } from "react";
import { useAuth } from "./AuthContext";
import axios from "../../utils/axios";

interface CartItem {
  id: number;
  product_id: number;
  variant_id: number;
  quantity: number;
  price: number;
  total: number;
  selected_attributes?: Record<string, string>;
  product: {
    id: number;
    name: string;
    image_url: string | null;
    brand?: string | null;
    category_name?: string | null;
  };
  variant: {
    id: number;
    title: string;
    sku: string;
    stock: number;
    image_url: string | null;
    bs?: number | null;
    mrp?: number | null;
    sp?: number | null;
  };
}

interface CartContextType {
  items: CartItem[];
  count: number;
  total: number;
  loading: boolean;
  addToCart: (productId: number, variantId: number, quantity: number, selectedAttributes?: Record<string, string>) => Promise<boolean>;
  updateQuantity: (cartItemId: number, quantity: number) => Promise<boolean>;
  removeFromCart: (cartItemId: number) => Promise<boolean>;
  clearCart: () => Promise<boolean>;
  refreshCart: () => Promise<void>;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export const CartProvider = ({ children }: { children: React.ReactNode }) => {
  const [items, setItems] = useState<CartItem[]>([]);
  const [count, setCount] = useState(0);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);

  const { user } = useAuth();

  const refreshCart = async () => {
    if (!user) {
      setItems([]);
      setCount(0);
      setTotal(0);
      return;
    }

    try {
      setLoading(true);
      const response = await axios.get('/api/cart');

      if (response.data.success) {
        const cartData = response.data.data;
        setItems(cartData.items);
        setCount(cartData.count);
        setTotal(cartData.total);
      }
    } catch (error) {
      console.error("Failed to fetch cart:", error);
      setItems([]);
      setCount(0);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  };

  const addToCart = async (
    productId: number,
    variantId: number,
    quantity: number = 1,
    selectedAttributes?: Record<string, string>
  ): Promise<boolean> => {
    if (!user) {
      alert("Please login to add items to cart");
      return false;
    }

    try {
      setLoading(true);
      const response = await axios.post('/api/cart/add', {
        product_id: productId,
        variant_id: variantId,
        quantity,
        selected_attributes: selectedAttributes,
      });

      if (response.data.success) {
        await refreshCart(); // Refresh cart to get updated data
        return true;
      }
      return false;
    } catch (error: any) {
      console.error("Failed to add item to cart:", error);

      // Handle specific error messages
      if (error.response?.data?.errors) {
        const errors = error.response.data.errors;
        const errorMessage = Object.values(errors).flat().join(', ');
        alert(errorMessage);
      } else if (error.response?.data?.message) {
        alert(error.response.data.message);
      } else {
        alert("Failed to add item to cart");
      }
      return false;
    } finally {
      setLoading(false);
    }
  };

  const updateQuantity = async (cartItemId: number, quantity: number): Promise<boolean> => {
    if (!user) return false;

    try {
      setLoading(true);
      const response = await axios.put(`/api/cart/${cartItemId}`, {
        quantity,
      });

      if (response.data.success) {
        await refreshCart();
        return true;
      }
      return false;
    } catch (error: any) {
      console.error("Failed to update cart item:", error);

      if (error.response?.data?.errors) {
        const errors = error.response.data.errors;
        const errorMessage = Object.values(errors).flat().join(', ');
        alert(errorMessage);
      } else {
        alert("Failed to update cart item");
      }
      return false;
    } finally {
      setLoading(false);
    }
  };

  const removeFromCart = async (cartItemId: number): Promise<boolean> => {
    if (!user) return false;

    try {
      setLoading(true);
      const response = await axios.delete(`/api/cart/${cartItemId}`);

      if (response.data.success) {
        await refreshCart();
        return true;
      }
      return false;
    } catch (error) {
      console.error("Failed to remove item from cart:", error);
      alert("Failed to remove item from cart");
      return false;
    } finally {
      setLoading(false);
    }
  };

  const clearCart = async (): Promise<boolean> => {
    if (!user) return false;

    try {
      setLoading(true);
      const response = await axios.delete('/api/cart');

      if (response.data.success) {
        setItems([]);
        setCount(0);
        setTotal(0);
        return true;
      }
      return false;
    } catch (error) {
      console.error("Failed to clear cart:", error);
      alert("Failed to clear cart");
      return false;
    } finally {
      setLoading(false);
    }
  };

  // Refresh cart when user changes
  useEffect(() => {
    refreshCart();
  }, [user]);

  return (
    <CartContext.Provider
      value={{
        items,
        count,
        total,
        loading,
        addToCart,
        updateQuantity,
        removeFromCart,
        clearCart,
        refreshCart,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error("useCart must be used within CartProvider");
  }
  return context;
};