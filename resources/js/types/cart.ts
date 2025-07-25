// resources/js/types/cart.ts

// Define the structure of a product nested within a cart item
export interface ProductInCart {
    id: number;
    title: string; // This is the product name (e.g., "چای سیاه ممتاز لاهیجان")
    slug: string | null;
    description: string;
    price: string; // Product's own price, as a string (e.g., "180000.00")
    stock: number;
    reserved_stock: number;
    status: string;
    image: string | null; // Product image URL (can be null)
    category_id: number;
    // Add other product fields if needed based on your API response
}

// Define the structure of a single item in the cart
export interface CartItem {
    id: string; // Cart item ID (e.g., "113")
    cart_id: number;
    product_id: number;
    quantity: number;
    price: string; // The price of this specific item in the cart (per unit), as a string
    created_at: string;
    updated_at: string;
    product_variant_id: string | null; // Can be string or null
    user_id: string | null; // Can be string or null
    product: ProductInCart; // Nested product details, MANDATORY
    product_variant: any | null; // Can be null or any other type if variants are used
}

// Define the overall structure of the cart state
export interface CartState {
    items: CartItem[];
    total: number;
    subtotal: number;
    discount: number;
    shipping: number;
    tax: number;
    loading: boolean;
    error: string | null;
}
