document.addEventListener("DOMContentLoaded", function() {
    const userId = document.getElementById("userId").value;
    const uploadCard = document.getElementById("permitUploadCard");
    const displayCard = document.getElementById("permitDisplayCard");
    const form = document.getElementById("businessPermitForm");

    // Fetch existing permit
    fetch(`php/get_permit.php?userId=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showDisplayCard(data.data);
            } else {
                uploadCard.style.display = "block";
                displayCard.style.display = "none";
            }
        });

    // Handle form submit
    form.addEventListener("submit", e => {
        e.preventDefault();
        const formData = new FormData(form);

        fetch("php/save_business_permit.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.status === "success") location.reload();
        });
    });

    // Handle update button
    document.getElementById("updatePermit").addEventListener("click", () => {
        uploadCard.style.display = "block";
        displayCard.style.display = "none";
    });

    function showDisplayCard(data) {
        uploadCard.style.display = "none";
        displayCard.style.display = "block";

        document.getElementById("displayPermitNumber").textContent = data.permit_number;
        document.getElementById("displayBusinessName").textContent = data.business_name;
        document.getElementById("displayIssueDate").textContent = data.issue_date;
        document.getElementById("displayExpiryDate").textContent = data.expiry_date;
        document.getElementById("displayUploadDate").textContent = data.created_at;

        if (data.file_path) {
            document.getElementById("permitImageContainer").innerHTML =
                data.mime_type.includes("pdf")
                    ? `<embed src="${data.file_path}" type="application/pdf" width="100%" height="400px">`
                    : `<img src="${data.file_path}" alt="Business Permit" class="uploaded-image">`;
        }
    }
});
