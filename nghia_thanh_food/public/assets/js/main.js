// Add to cart function
function addToCart(productId) {
  window.location.href = "/public/pages/cart.php?action=add&id=" + productId;
}

// Add to cart with quantity
function addToCartWithQuantity(productId, quantity = 1) {
  window.location.href =
    "/public/pages/cart.php?action=add&id=" +
    productId +
    "&quantity=" +
    quantity;
}

// Buy now
function buyNow(productId) {
  let quantity = document.getElementById("quantity")
    ? document.getElementById("quantity").value
    : 1;

  window.location.href =
    "/public/pages/checkout.php?buy_now=1&id=" +
    productId +
    "&quantity=" +
    quantity;
}

// Update cart quantity
function updateCartQuantity(productId, quantity) {
  if (quantity < 1) {
    if (confirm("Bạn có chắc muốn xóa sản phẩm này?")) {
      window.location.href = "/public/pages/cart.php?remove=1&id=" + productId;
    }
  } else {
    // Submit form to update cart
    document.getElementById("cart-form").submit();
  }
}

// Validate checkout form
function validateCheckout() {
  let form = document.getElementById("checkoutForm");
  if (form) {
    let required = form.querySelectorAll("[required]");
    for (let field of required) {
      if (!field.value.trim()) {
        alert("Vui lòng điền đầy đủ thông tin!");
        field.focus();
        return false;
      }
    }

    let email = form.querySelector('input[type="email"]');
    if (email && email.value) {
      let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email.value)) {
        alert("Email không hợp lệ!");
        email.focus();
        return false;
      }
    }

    let phone = form.querySelector('input[type="tel"]');
    if (phone && phone.value) {
      let phoneRegex = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
      if (!phoneRegex.test(phone.value)) {
        alert("Số điện thoại không hợp lệ!");
        phone.focus();
        return false;
      }
    }
  }
  return true;
}

// Search products with debounce
let searchTimeout;
function searchProducts() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(function () {
    let keyword = document.getElementById("searchInput").value;
    if (keyword.length >= 2 || keyword.length === 0) {
      window.location.href =
        "/public/pages/products.php?search=" + encodeURIComponent(keyword);
    }
  }, 500);
}

// Show loading
function showLoading() {
  let loader = document.createElement("div");
  loader.className = "loading";
  loader.innerHTML = '<div class="spinner"></div>';
  document.body.appendChild(loader);
}

function hideLoading() {
  let loader = document.querySelector(".loading");
  if (loader) loader.remove();
}

// Auto hide alerts after 3 seconds
document.addEventListener("DOMContentLoaded", function () {
  let alerts = document.querySelectorAll(".alert");
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = "opacity 0.5s";
      alert.style.opacity = "0";
      setTimeout(function () {
        alert.remove();
      }, 500);
    }, 3000);
  });
});

// Add to cart animation
function animateAddToCart(button) {
  button.innerHTML = '<i class="fas fa-check me-1"></i>Đã thêm';
  button.classList.add("btn-success");
  button.classList.remove("btn-outline-success");
  setTimeout(function () {
    button.innerHTML = '<i class="fas fa-shopping-cart me-1"></i>Thêm vào giỏ';
    button.classList.remove("btn-success");
    button.classList.add("btn-outline-success");
  }, 2000);
}

// Scroll to top button
window.addEventListener("scroll", function () {
  let scrollBtn = document.getElementById("scrollTop");
  if (scrollBtn) {
    if (window.scrollY > 300) {
      scrollBtn.style.display = "block";
    } else {
      scrollBtn.style.display = "none";
    }
  }
});

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
}

// Add scroll to top button to page
document.addEventListener("DOMContentLoaded", function () {
  let btn = document.createElement("button");
  btn.id = "scrollTop";
  btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
  btn.style.position = "fixed";
  btn.style.bottom = "30px";
  btn.style.right = "30px";
  btn.style.width = "50px";
  btn.style.height = "50px";
  btn.style.borderRadius = "50%";
  btn.style.backgroundColor = "#2e7d32";
  btn.style.color = "white";
  btn.style.border = "none";
  btn.style.cursor = "pointer";
  btn.style.display = "none";
  btn.style.zIndex = "999";
  btn.style.transition = "all 0.3s";
  btn.onclick = scrollToTop;
  document.body.appendChild(btn);
});
