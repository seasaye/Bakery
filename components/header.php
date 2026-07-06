<nav class="navbar">
    <div class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <span class="navbar-brand"><?= $judul ?></span>

    <div class="user-info">
        <span class="jam-digital" id="jam"></span>
        <span><?= date('d M Y') ?></span>
        <img src="/bakery/assets/img/user2.jpg" class="rounded-circle" width="35">
    </div>
</nav>