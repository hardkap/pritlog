function confirm_delete(url) {
    var answer = confirm ("Are you sure?")
    if (answer)
        window.location=url;
}