self.addEventListener("fetch", function (event) {
    event.respondWith(
      fetch(event.request).catch(() => {
        return new Response(
          `<html><body style="background-color:#000;color:#fff;font-family:sans-serif;text-align:center;margin-top:20%;">
            <h2><strong>ERR_INTERNET_DISCONNECTED</strong></h2>
            <h3>No hay conexion a internet: Conectate a internet para poder usar SharedBible.</h3>
            <h1 style="transform: scale(1.4); margin-top:5%;">:(</h1>
          </body></html>`,
          { headers: { "Content-Type": "text/html" } }
        );
      })
    );
  });
  