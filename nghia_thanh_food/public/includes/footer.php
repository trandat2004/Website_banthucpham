<footer class="footer mt-auto py-5 text-white">

    <div class="container">

        <div class="row gy-4">

            <!-- LOGO + THÔNG TIN -->
            <div class="col-lg-4 col-md-6">

                <a href="<?php echo BASE_URL; ?>/index.php"
                   class="footer-brand text-decoration-none d-inline-block mb-4">

                    <div class="footer-brand-top">
                        NGHĨA THÀNH
                    </div>

                    <div class="footer-brand-bottom">
                        EXPORT FOOD PROCESSING
                    </div>

                </a>

                <div class="footer-contact">

                    <div class="footer-contact-item">

                        <i class="fas fa-location-dot footer-contact-icon"></i>

                        <span>
                            <?php echo getConfig('company_address'); ?>
                        </span>

                    </div>

                    <div class="footer-contact-item">

                        <i class="fas fa-phone footer-contact-icon"></i>

                        <span>
                            <?php echo getConfig('company_phone'); ?>
                        </span>

                    </div>

                    <div class="footer-contact-item">

                        <i class="fas fa-envelope footer-contact-icon"></i>

                        <span>
                            <?php echo getConfig('company_email'); ?>
                        </span>

                    </div>

                </div>

            </div>

            <!-- LIÊN KẾT -->
            <div class="col-lg-3 col-md-6">

                <h5 class="footer-title">
                    Liên kết
                </h5>

                <ul class="footer-links list-unstyled">

                    <li>
                        <a href="<?php echo BASE_URL; ?>/public/pages/products.php">
                            Sản phẩm
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/public/pages/about.php">
                            Giới thiệu
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/public/pages/news.php">
                            Tin tức
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/public/pages/contact.php">
                            Liên hệ
                        </a>
                    </li>

                </ul>

            </div>

            <!-- CHÍNH SÁCH -->
            <div class="col-lg-3 col-md-6">

                <h5 class="footer-title">
                    Chính sách
                </h5>

                <ul class="footer-links list-unstyled">

                    <li>
                        <a href="<?php echo BASE_URL; ?>/public/pages/policies/return-policy.php">
                            Chính sách đổi trả
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/public/pages/policies/shipping-policy.php">
                            Chính sách giao hàng
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/public/pages/policies/privacy-policy.php">
                            Bảo mật thông tin
                        </a>
                    </li>

                    <li>
                        <a href="<?php echo BASE_URL; ?>/public/pages/policies/terms-of-use.php">
                            Điều khoản sử dụng
                        </a>
                    </li>

                </ul>

            </div>

            <!-- KẾT NỐI -->
            <div class="col-lg-2 col-md-6">

                <h5 class="footer-title">
                    Kết nối
                </h5>

                <div class="social-links">
    <?php 
    $facebook_url = getConfig('facebook_url');
    $instagram_url = getConfig('instagram_url');
    $youtube_url = getConfig('youtube_url');
    ?>

    <?php if (!empty($facebook_url)): ?>
    <a href="<?php echo $facebook_url; ?>" class="btn facebook" target="_blank">
        <i class="fab fa-facebook-f"></i>
    </a>
    <?php endif; ?>

    <?php if (!empty($instagram_url)): ?>
    <a href="<?php echo $instagram_url; ?>" class="btn instagram" target="_blank">
        <i class="fab fa-instagram"></i>
    </a>
    <?php endif; ?>

    <?php if (!empty($youtube_url)): ?>
    <a href="<?php echo $youtube_url; ?>" class="btn youtube" target="_blank">
        <i class="fab fa-youtube"></i>
    </a>
    <?php endif; ?>
</div>

            </div>

        </div>

    </div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/assets/js/main.js"></script>

</body>
</html>