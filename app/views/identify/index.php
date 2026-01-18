<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">AI Part Recognition</h4>
            </div>
            <div class="card-body text-center py-5">
                <p class="lead mb-4">Upload a photo of your LEGO® parts to automatically identify them.</p>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="/identify/analyze" method="POST" enctype="multipart/form-data" class="d-flex flex-column align-items-center">
                    <div class="mb-4 w-75">
                        <label for="image" class="form-label visually-hidden">Upload Image</label>
                        <input class="form-control form-control-lg" type="file" id="image" name="image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-lg btn-success px-5">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                        Analyze Image
                    </button>
                </form>
                
                <div class="mt-4 text-muted small">
                    On desktop you can upload an existing image. On mobile or tablet, your browser will let you choose from gallery or use the camera.
                </div>
                <div class="mt-1 text-muted small">
                    Supports JPG, PNG. Make sure parts are clearly visible.
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    const spinner = btn.querySelector('.spinner-border');
    btn.disabled = true;
    spinner.classList.remove('d-none');
    btn.childNodes[2].textContent = ' Processing...';
});
</script>
