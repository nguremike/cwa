$(function () {
  toastr.options = {
    positionClass: "toast-bottom-right",
    progressBar: true,
    timeOut: 3500,
  };

  window.api = function (url, data, method = "GET") {
    return $.ajax({
      url: window.CWA.base + url,
      method: method,
      data: data,
      dataType: "json",
      headers: { "X-CSRF-Token": window.CWA.csrf },
    });
  };
});
