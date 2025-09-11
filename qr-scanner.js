document.addEventListener("DOMContentLoaded", function() {
    const openBtn = document.getElementById("open-qr-btn");
    const closeBtn = document.getElementById("close-qr-btn");
    const readerWrapper = document.getElementById("qr-reader-wrapper");
    const resultDiv = document.getElementById("qr-result");
    let qrScanner = null;

    if (openBtn) {
        openBtn.addEventListener("click", function() {
            readerWrapper.style.display = "block";
            openBtn.style.display = "none";
            closeBtn.style.display = "inline-block";

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
                    try {
                        const data = JSON.parse(qrCodeMessage);
                        resultDiv.innerHTML = `
                            ✅ Ticket Scanned<br>
                            Guest: ${data.guest} <br>
                            Event: ${data.event} <br>
                            Ticket Code: ${data.ticket}
                        `;
                    } catch (e) {
                        resultDiv.innerHTML = "Scanned: " + qrCodeMessage;
                    }
                },
                err => console.warn("QR scan error:", err)
            ).catch(err => {
                console.error("Camera start failed:", err);
                resultDiv.innerHTML = "⚠️ Unable to access camera. Make sure you allow camera permissions and use HTTPS/localhost.";
            });
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", function() {
            if (qrScanner) {
                qrScanner.stop().then(() => {
                    qrScanner.clear();
                    readerWrapper.style.display = "none";
                    openBtn.style.display = "inline-block";
                    closeBtn.style.display = "none";
                }).catch(err => {
                    console.error("Failed to stop scanner:", err);
                });
            }
        });
    }
});
