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
        .card-footer form { margin-bottom: 0; }
        @media (max-width: 576px) {
            .navbar .form-select { width: 90px !important; }
            .navbar .form-control { min-width: 120px; }
        }
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
                    <li class="nav-item"><a class="nav-link" href="/identify">Identify (AI)</a></li>
                    <li class="nav-item"><a class="nav-link" href="/my/sets">My sets</a></li>
                    <li class="nav-item"><a class="nav-link" href="/my/parts">My parts</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/update">Update</a></li>
                </ul>
            </div>
            <form class="d-flex position-relative ms-lg-3 mt-2 mt-lg-0" action="/search" method="get" id="searchForm">
                <select name="type" class="form-select me-2" style="width: 100px;" id="searchType">
                    <option value="parts" selected>Parts</option>
                    <option value="sets">Sets</option>
                </select>
                <input class="form-control me-2" type="search" name="q" id="searchInput" placeholder="Search" aria-label="Search" autocomplete="off">
                <button class="btn btn-outline-light" type="submit">Search</button>
                <div id="searchResults" class="position-absolute bg-white text-dark shadow rounded" style="top: 100%; left: 0; right: 0; z-index: 1000; max-height: 400px; overflow-y: auto; display: none;"></div>
            </form>
        </div>
    </nav>

    <div class="container">
        <?php include $viewPath; ?>
    </div>

    <footer class="text-center mt-5 py-3 text-muted">
        &copy; <?= date('Y') ?> Lego Inventory
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchType = document.getElementById('searchType');
            const searchResults = document.getElementById('searchResults');
            let debounceTimer;

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                const type = searchType.value;

                clearTimeout(debounceTimer);
                
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    searchResults.innerHTML = '';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`/api/search?q=${encodeURIComponent(query)}&type=${type}`)
                        .then(response => response.json())
                        .then(data => {
                            searchResults.innerHTML = '';
                            if (data.length > 0) {
                                const list = document.createElement('div');
                                list.className = 'list-group list-group-flush';
                                
                                data.forEach(item => {
                                    const a = document.createElement('a');
                                    a.className = 'list-group-item list-group-item-action d-flex align-items-center';
                                    
                                    let imgUrl = item.img_url;
                                    if (!imgUrl || (!imgUrl.startsWith('/images') && !imgUrl.startsWith('/parts_images'))) {
                                        imgUrl = '/images/no-image.png';
                                    }
                                    
                                    const linkUrl = type === 'sets' ? `/sets/${item.set_num}` : `/parts/${item.part_num}`;
                                    const itemNum = type === 'sets' ? item.set_num : item.part_num;
                                    
                                    a.href = linkUrl;
                                    a.innerHTML = `
                                        <img src="${imgUrl}" alt="${item.name}" class="me-3" style="width: 40px; height: 40px; object-fit: contain;">
                                        <div>
                                            <div class="fw-bold text-truncate" style="max-width: 200px;">${item.name}</div>
                                            <small class="text-muted">${itemNum}</small>
                                        </div>
                                    `;
                                    list.appendChild(a);
                                });
                                
                                searchResults.appendChild(list);
                                searchResults.style.display = 'block';
                            } else {
                                searchResults.innerHTML = '<div class="p-3 text-muted">No results found</div>';
                                searchResults.style.display = 'block';
                            }
                        })
                        .catch(err => {
                            console.error('Search error:', err);
                        });
                }, 300);
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!document.getElementById('searchForm').contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
