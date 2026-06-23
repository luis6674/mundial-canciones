<?php
/**
 * Reusable login form: two newsletter checkboxes + Spotify button.
 * Include this wherever a login CTA is needed.
 * Requires config.php to already be loaded.
 */
$_lf_base     = htmlspecialchars(PRESAVE_BASE_URL, ENT_QUOTES);
$_lf_list1    = htmlspecialchars(PRESAVE_LIST_REQUIRED, ENT_QUOTES);
$_lf_list2    = htmlspecialchars(PRESAVE_LIST_OPTIONAL, ENT_QUOTES);
$_lf_privacy  = 'https://www.sonymusic.es/politica-de-privacidad-y-cookies/';
?>
<div class="login-form" id="login-form">
  <div class="login-checkboxes">
    <label class="checkbox-label" for="lf-check-1">
      <input type="checkbox" id="lf-check-1" aria-describedby="lf-error">
      <span>He leído y acepto la <a href="<?= htmlspecialchars($_lf_privacy) ?>" target="_blank" rel="noopener">Política de Privacidad</a> y, en consecuencia, deseo recibir información sobre Pop a través de correo electrónico, SMS, Whatsapp u otros medios electrónicos.</span>
    </label>
    <label class="checkbox-label" for="lf-check-2">
      <input type="checkbox" id="lf-check-2">
      <span>Quiero recibir información comercial, concursos, material promocional de SONY MUSIC ENTERTAINMENT ESPAÑA, S.L. y sus artistas a través de correo electrónico, SMS, Whatsapp u otros medios electrónicos.</span>
    </label>
  </div>
  <p class="login-form-error" id="lf-error" role="alert" hidden>Debes aceptar la Política de Privacidad para continuar.</p>
  <button type="button" class="btn btn-spotify btn-lg" id="lf-btn">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.516 17.311c-.217.356-.666.468-1.022.252-2.797-1.709-6.319-2.095-10.465-1.148-.4.091-.8-.158-.891-.558-.092-.4.158-.8.558-.891 4.538-1.037 8.43-.591 11.568 1.323.357.217.469.666.252 1.022zm1.471-3.27c-.272.44-.851.578-1.291.306-3.201-1.967-8.082-2.537-11.87-1.389-.492.148-1.013-.133-1.162-.625-.148-.492.133-1.013.625-1.162 4.327-1.314 9.703-.677 13.386 1.579.44.272.578.851.306 1.291zm.128-3.403c-3.841-2.28-10.178-2.49-13.845-1.377-.589.18-1.211-.153-1.391-.742-.18-.59.153-1.211.742-1.391 4.21-1.279 11.204-1.031 15.626 1.593.53.315.706 1.001.39 1.531-.314.53-1 .706-1.53.39l.008-.004z"/></svg>
    Entrar con Spotify
  </button>
</div>
<script>
(function () {
  var base  = '<?= $_lf_base ?>';
  var list1 = '<?= $_lf_list1 ?>';
  var list2 = '<?= $_lf_list2 ?>';

  var check1 = document.getElementById('lf-check-1');
  var check2 = document.getElementById('lf-check-2');
  var btn    = document.getElementById('lf-btn');
  var error  = document.getElementById('lf-error');

  check1.addEventListener('change', function () { error.hidden = true; });

  btn.addEventListener('click', function () {
    if (!check1.checked) {
      error.hidden = false;
      return;
    }
    error.hidden = true;
    var ids = check2.checked ? list1 + ',' + list2 : list1;
    window.location.href = base + '&mailing_list_ids=' + encodeURIComponent(ids);
  });
})();
</script>
