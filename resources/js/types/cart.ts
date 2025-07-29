// resources/js/types/cart.ts

// Define the structure of a product nested within a cart item
export interface ProductInCart {
    id: number;
    name: string; // Changed from 'title' to 'name' based on logs
    inStock: boolean; // Added based on logs
    slug: string | null;
    image: string | null; // Product image URL (can be null)
    stockQuantity: number; // Added based on logs
    // Other product fields from your API response can be added here
}

// Define the structure of a single item in the cart
export interface CartItem {
    id: string; // Cart item ID (e.g., "113")
    product_id: number; // Added based on logs
    quantity: number;
    unitPrice: number; // Changed from 'price: string' to 'unitPrice: number'
    totalPrice: number; // Added based on logs
    formattedUnitPrice: string; // Added for the already formatted price string
    formattedTotalPrice: string; // Added for the already formatted total price string
    addedAt: string; // Added based on logs
    updatedAt: string; // Added based on logs
    product: ProductInCart; // Nested product details, MANDATORY
    // Other fields like cart_id, product_variant_id, user_id can be added if needed
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
