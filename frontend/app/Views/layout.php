<!DOCTYPE html>
<html lang="en">

<head>
    <?= $header ?>
    <style>
    body {
        margin: 0;
        padding: 0;
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        transition: margin-left 0.3s;
    }

    .sidebar {
        width: 200px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: #f8f9fa;
        padding: 20px;
        transform: translateX(0);
        transition: transform 0.3s ease;
        z-index: 1000;
    }

    .sidebar.closed {
        transform: translateX(-200px);
    }

    .main-content {
        margin-left: 200px;
        padding: 20px;
        transition: margin-left 0.3s;
    }

    .main-content.expanded {
        margin-left: 0;
    }

    .btn-hamburger {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        cursor: pointer;
    }

    .btn-hamburger img {
        width: 24px;
        height: 24px;
    }

    .small-logo {
        width: 50px;
        height: auto;
    }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <?= $sidebar ?>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <?= $navbar ?>
        <div class="container mt-4">
            <?= $chart_view ?>
        </div>
    </div>

    <?= $footer ?>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('sidebar');
        var mainContent = document.getElementById('mainContent');
        var sidebarToggle = document.getElementById('sidebarToggle');

        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('closed');
            mainContent.classList.toggle('expanded');
        });
    });
    </script>
</body>

</html>