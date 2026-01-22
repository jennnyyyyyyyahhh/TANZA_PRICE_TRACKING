
document.querySelectorAll('.btn-outline-primary').forEach(btn => {
  btn.addEventListener('click', function() {
    const row = this.closest('tr');
    const surveyId = row.querySelector('input[name="survey_id"]')?.value || 
                     this.getAttribute('data-id');

    fetch(`php/fetch_survey.php?id=${surveyId}`)
      .then(res => res.json())
      .then(data => {
        if (data.error) return alert(data.error);

        document.getElementById('editSurveyId').value = data.id;
        document.getElementById('editProductName').value = data.product_name;
        document.getElementById('editCommodityType').value = data.commodity_type;
        document.getElementById('editVariety').value = data.variety;
        document.getElementById('editPrice').value = data.price;
        document.getElementById('editQuantityType').value = data.quantity_type;
        document.getElementById('editQuality').value = data.quality;
        document.getElementById('editNote').value = data.note;
        document.getElementById('editPreviewImage').src = data.product_image 
          ? `uploads/${data.product_image}`
          : 'https://via.placeholder.com/450x300?text=No+Image';

        new bootstrap.Modal(document.getElementById('editSurveyModal')).show();
      })
      .catch(err => console.error(err));
  });
});

// Preview uploaded image inside edit modal
document.getElementById('editProductImage').addEventListener('change', e => {
  const file = e.target.files[0];
  const preview = document.getElementById('editPreviewImage');
  if (file) {
    const reader = new FileReader();
    reader.onload = e => preview.src = e.target.result;
    reader.readAsDataURL(file);
  }
});

document.getElementById('productImage').addEventListener('change', function (event) {
            const preview = document.getElementById('previewImage');
            const file = event.target.files[0];
            if (file) {
            const reader = new FileReader();
            reader.onload = e => preview.src = e.target.result;
            reader.readAsDataURL(file);
            } else {
            preview.src = "https://via.placeholder.com/450x300?text=No+Image";
            }
        });

function showNotif(message, type = 'success') {
    const notif = document.getElementById('notif');
    const notifMsg = document.getElementById('notifMsg');
    const closeBtn = document.getElementById('notifClose');

    notifMsg.textContent = message;
    notif.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';

    notif.style.display = 'block';
    setTimeout(() => notif.style.opacity = '1', 10);

    const timer = setTimeout(() => hideNotif(true), 3000);

    closeBtn.onclick = () => {
        clearTimeout(timer);
        hideNotif(true);
    };
}

function hideNotif(clearUrl = false) {
    const notif = document.getElementById('notif');
    notif.style.opacity = '0';
    setTimeout(() => {
        notif.style.display = 'none';
        if (clearUrl) {
            const baseUrl = window.location.origin + window.location.pathname;
            window.history.replaceState({}, document.title, baseUrl);
        }
    }, 300);
}

// ✅ Detect URL query parameters and show corresponding notifications
const params = new URLSearchParams(window.location.search);

if (params.get('success') === '1') showNotif('Survey submitted successfully!', 'success');
if (params.get('deleted') === '1') showNotif('Survey deleted successfully!', 'success');
if (params.get('updated') === '1') showNotif('Survey updated successfully!', 'success');
if (params.get('error')) showNotif(params.get('error'), 'error');