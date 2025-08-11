<!-- Modal untuk menampilkan gambar full size -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50" onclick="closeModal()">
    <div class="relative max-w-screen-lg max-h-screen-lg p-4">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-white text-4xl font-bold hover:text-gray-300 z-10">
            ×
        </button>
        <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded-lg">
        <div id="modalCaption" class="text-white text-center mt-4 text-lg font-medium"></div>
    </div>
</div>
<script>
    function openModal(imageSrc, caption) {
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const modalCaption = document.getElementById('modalCaption');
        
        modalImage.src = imageSrc;
        modalCaption.textContent = caption;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('imageModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        
        // Restore body scroll
        document.body.style.overflow = 'auto';
    }

    // Close modal with ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    // Close modal when clicking outside image
    document.getElementById('imageModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeModal();
        }
    });
</script>