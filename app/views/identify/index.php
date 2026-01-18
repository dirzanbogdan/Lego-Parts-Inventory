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

                <form action="/identify/analyze" method="POST" enctype="multipart/form-data" class="d-flex flex-column align-items-center" id="identifyForm">
                    <input type="file" id="image_gallery" name="image_gallery" accept="image/*" class="d-none">
                    <input type="file" id="image_camera" name="image_camera" accept="image/*" capture="environment" class="d-none">
                    <div class="mb-4 w-75 d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-lg flex-fill" id="btnUpload">
                            Upload image
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-lg flex-fill d-none" id="btnCamera">
                            Take photo
                        </button>
                    </div>
                    <button type="submit" class="btn btn-lg btn-success px-5" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                        Analyze Image
                    </button>
                </form>
                
                <div class="mt-4 text-muted small">
                    On desktop you can upload an existing image. On mobile or tablet you can upload from gallery or take a new photo.
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
const form = document.getElementById('identifyForm');
const btnUpload = document.getElementById('btnUpload');
const btnCamera = document.getElementById('btnCamera');
const inputGallery = document.getElementById('image_gallery');
const inputCamera = document.getElementById('image_camera');
const submitBtn = document.getElementById('submitBtn');
const submitSpinner = submitBtn.querySelector('.spinner-border');

function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

if (isMobile()) {
    btnCamera.classList.remove('d-none');
}

btnUpload.addEventListener('click', function () {
    inputCamera.value = '';
    inputGallery.click();
});

btnCamera.addEventListener('click', function () {
    inputGallery.value = '';
    inputCamera.click();
});

form.addEventListener('submit', function (e) {
    if (!inputGallery.files.length && !inputCamera.files.length) {
        e.preventDefault();
        if (isMobile()) {
            inputGallery.click();
        } else {
            inputGallery.click();
        }
        return;
    }
    submitBtn.disabled = true;
    submitSpinner.classList.remove('d-none');
    submitBtn.childNodes[2].textContent = ' Processing...';
});
</script>
