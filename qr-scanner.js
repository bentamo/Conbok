document.addEventListener("DOMContentLoaded", function() {
    const openBtn = document.getElementById("open-qr-btn");
    const closeBtn = document.getElementById("close-qr-btn");
    const readerWrapper = document.getElementById("qr-reader-wrapper");
    const resultDiv = document.getElementById("qr-result");
    let qrScanner = null;

    openBtn.addEventListener("click", function() {
        readerWrapper.style.display = "block";
        openBtn.style.display = "none";

        if (typeof Html5Qrcode === "undefined") {
            console.error("Html5Qrcode library not loaded!");
            resultDiv.innerHTML = "⚠️ Scanner library not found.";
            return;
        }

        qrScanner = new Html5Qrcode("qr-reader");

        qrScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            qrCodeMessage => {
                resultDiv.innerHTML = "Scanned: " + qrCodeMessage;

                // Optional: make links clickable
                if (qrCodeMessage.startsWith("http")) {
                    resultDiv.innerHTML = `Scanned: <a href="${qrCodeMessage}" target="_blank">${qrCodeMessage}</a>`;
                }
            },
            err => {
                console.warn("QR scan error:", err);
            }
        ).catch(err => {
            console.error("Camera start failed:", err);
            resultDiv.innerHTML = "⚠️ Unable to access camera. Are you on HTTPS?";
        });
    });

    closeBtn.addEventListener("click", function() {
        if (qrScanner) {
            qrScanner.stop().then(() => {
                qrScanner.clear();
                readerWrapper.style.display = "none";
                openBtn.style.display = "inline-block";
            }).catch(err => {
                console.error("Failed to stop scanner:", err);
            });
        }
    });
});
