<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lego Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .part-img { width: 50px; height: 50px; object-fit: contain; }
        .set-img { width: 100%; height: 200px; object-fit: contain; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/">Lego Inventory</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/sets">Sets</a></li>
                    <li class="nav-item"><a class="nav-link" href="/parts">Parts</a></li>
                    <li class="nav-item"><a class="nav-link" href="/themes">Themes</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/update">Update</a></li>
                </ul>
                <form class="d-flex" action="/search" method="get">
                    <select name="type" class="form-select me-2" style="width: 100px;">
                        <option value="sets">Sets</option>
                        <option value="parts">Parts</option>
                    </select>
                    <input class="form-control me-2" type="search" name="q" placeholder="Search" aria-label="Search">
                    <button class="btn btn-outline-light" type="submit">Search</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php include $viewPath; ?>
    </div>

    <footer class="text-center mt-5 py-3 text-muted">
        &copy; <?= date('Y') ?> Lego Inventory
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
