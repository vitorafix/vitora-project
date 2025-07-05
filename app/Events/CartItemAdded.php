// File: app/Events/CartItemAdded.php
namespace App\Events;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartItemAdded
{
    use Dispatchable, SerializesModels;

    public Cart $cart;
    public Product $product;
    public int $quantity;

    public function __construct(Cart $cart, Product $product, int $quantity)
    {
        $this->cart = $cart;
        $this->product = $product;
        $this->quantity = $quantity;
    }
}

// File: app/Events/CartItemUpdated.php
namespace App\Events;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartItemUpdated
{
    use Dispatchable, SerializesModels;

    public Cart $cart;
    public CartItem $cartItem;
    public int $oldQuantity;
    public int $newQuantity;

    public function __construct(Cart $cart, CartItem $cartItem, int $oldQuantity, int $newQuantity)
    {
        $this->cart = $cart;
        $this->cartItem = $cartItem;
        $this->oldQuantity = $oldQuantity;
        $this->newQuantity = $newQuantity;
    }
}

// File: app/Events/CartItemRemoved.php
namespace App\Events;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartItemRemoved
{
    use Dispatchable, SerializesModels;

    public Cart $cart;
    public CartItem $cartItem;

    public function __construct(Cart $cart, CartItem $cartItem)
    {
        $this->cart = $cart;
        $this->cartItem = $cartItem;
    }
}

// File: app/Events/CartCleared.php
namespace App\Events;

use App\Models\Cart;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartCleared
{
    use Dispatchable, SerializesModels;

    public Cart $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }
}

// File: app/Events/CartMerged.php
namespace App\Events;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartMerged
{
    use Dispatchable, SerializesModels;

    public Cart $fromCart; // The cart that was merged FROM (e.g., guest cart)
    public Cart $toCart;   // The cart that was merged INTO (e.g., user cart)
    public User $user;

    public function __construct(Cart $fromCart, Cart $toCart, User $user)
    {
        $this->fromCart = $fromCart;
        $this->toCart = $toCart;
        $this->user = $user;
    }
}
