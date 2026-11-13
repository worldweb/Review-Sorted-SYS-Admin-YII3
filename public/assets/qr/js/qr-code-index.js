function downloadQRCodeJpg(filename, format, imageName) {
    var link = document.createElement("a");
    link.href = filename;
    if (format === "pdf") {
        link.download = "qrcode.pdf";
        link.target = "_blank";
    } else {
        link.download = imageName+"."+ format;
    }

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}