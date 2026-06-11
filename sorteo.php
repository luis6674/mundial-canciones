<?php
/**
 * sorteo.php — Giveaway entry form.
 * Requires the user to be logged in and to have voted.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user      = $_SESSION['user'] ?? null;
$logged_in = (bool)$user;

$has_voted  = false;
$user_email = '';

if ($logged_in) {
    $db = get_db();

    $stmt = $db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    $has_voted = (int)$stmt->fetchColumn() > 0;

    $stmt = $db->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([(int)$user['id']]);
    $user_email = $stmt->fetchColumn() ?: '';
}

render_header('Sorteo — Gana una camiseta');
?>

<div class="section-inner sorteo-page">
  <div class="sorteo-wrap">

    <h1 class="sorteo-title">&#127944; Gana una camiseta de <span class="accent">la Roja</span></h1>

    <?php if (!$logged_in): ?>
      <div class="login-prompt">
        <p>Necesitas iniciar sesión y votar para participar en el sorteo.</p>
        <a href="vote.php" class="btn btn-spotify">Ir a votar</a>
      </div>

    <?php elseif (!$has_voted): ?>
      <div class="login-prompt">
        <p>Debes votar primero para poder participar en el sorteo.</p>
        <a href="vote.php" class="btn btn-primary">Ir a votar</a>
      </div>

    <?php else: ?>

      <p class="sorteo-sub">Rellena el formulario para entrar en el sorteo. Una vez realizado el sorteo, si resultas ganador nos pondremos en contacto contigo.</p>

      <div id="sorteo-form-wrap">
        <form id="sorteo-form" novalidate>
          <input type="hidden" name="js_url" value="https://subs.sonymusicfans.com/submit">
          <input type="hidden" name="form"   value="755955">

          <div class="form-group">
            <input type="email" name="field_email_address" id="field_email_address"
              class="form-control"
              value="<?= htmlspecialchars($user_email) ?>"
              readonly
              placeholder="Email">
          </div>

          <div class="form-row">
            <div class="form-group">
              <input type="text" name="field_first_name" id="field_first_name"
                class="form-control" maxlength="30" placeholder="Nombre *" required>
              <div class="field-error">Introduce tu nombre</div>
            </div>
            <div class="form-group">
              <input type="text" name="field_last_name" id="field_last_name"
                class="form-control" maxlength="60" placeholder="Apellidos *" required>
              <div class="field-error">Introduce tus apellidos</div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <input type="text" name="field_city" id="field_city"
                class="form-control" maxlength="60" placeholder="Ciudad *" required>
              <div class="field-error">Introduce tu ciudad</div>
            </div>
            <div class="form-group">
              <select name="field_gender" id="field_gender" class="form-control" required>
                <option value="" disabled selected>Sexo *</option>
                <option value="Male">Hombre</option>
                <option value="Female">Mujer</option>
                <option value="Non-binary/Other">No binario / Otro</option>
                <option value="Prefer not to answer">Prefiero no responder</option>
              </select>
              <div class="field-error">Selecciona tu sexo</div>
            </div>
          </div>

          <div class="form-group">
            <select name="field_country_region" id="field_country_region" class="form-control" required>
              <option value="" disabled selected>País *</option>
              <option value="AF">Afghanistan</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AU">Australia</option><option value="AT">Austria</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BE">Belgium</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BT">Bhutan</option><option value="BO">Bolivia</option><option value="BA">Bosnia &amp; Herzegovina</option><option value="BW">Botswana</option><option value="BR">Brazil</option><option value="VG">British Virgin Islands</option><option value="BN">Brunei</option><option value="BG">Bulgaria</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="CV">Cape Verde</option><option value="KY">Cayman Islands</option><option value="CF">Central African Republic</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CO">Colombia</option><option value="KM">Comoros</option><option value="CG">Congo - Brazzaville</option><option value="CD">Congo - Kinshasa</option><option value="CR">Costa Rica</option><option value="HR">Croatia</option><option value="CU">Cuba</option><option value="CY">Cyprus</option><option value="CZ">Czechia</option><option value="CI">Côte d'Ivoire</option><option value="DK">Denmark</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="EE">Estonia</option><option value="SZ">Eswatini</option><option value="ET">Ethiopia</option><option value="FJ">Fiji</option><option value="FI">Finland</option><option value="FR">France</option><option value="GA">Gabon</option><option value="GM">Gambia</option><option value="GE">Georgia</option><option value="DE">Germany</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GR">Greece</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GT">Guatemala</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HN">Honduras</option><option value="HK">Hong Kong SAR China</option><option value="HU">Hungary</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IR">Iran</option><option value="IQ">Iraq</option><option value="IE">Ireland</option><option value="IL">Israel</option><option value="IT">Italy</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="XK">Kosovo</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Laos</option><option value="LV">Latvia</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libya</option><option value="LI">Liechtenstein</option><option value="LT">Lithuania</option><option value="LU">Luxembourg</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MT">Malta</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="MX">Mexico</option><option value="MD">Moldova</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar (Burma)</option><option value="NA">Namibia</option><option value="NP">Nepal</option><option value="NL">Netherlands</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger</option><option value="NG">Nigeria</option><option value="NO">Norway</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PS">Palestinian Territories</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH">Philippines</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="RO">Romania</option><option value="RU">Russia</option><option value="RW">Rwanda</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG">Singapore</option><option value="SK">Slovakia</option><option value="SI">Slovenia</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="KR">South Korea</option><option value="SS">South Sudan</option><option value="ES" selected>Spain</option><option value="LK">Sri Lanka</option><option value="SD">Sudan</option><option value="SR">Suriname</option><option value="SE">Sweden</option><option value="CH">Switzerland</option><option value="SY">Syria</option><option value="TW">Taiwan</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TO">Tonga</option><option value="TT">Trinidad &amp; Tobago</option><option value="TN">Tunisia</option><option value="TM">Turkmenistan</option><option value="TR">Türkiye</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates</option><option value="GB">United Kingdom</option><option value="US">United States</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VA">Vatican City</option><option value="VE">Venezuela</option><option value="VN">Vietnam</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option>
            </select>
            <div class="field-error">Selecciona tu país</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="field_dob">Fecha de nacimiento *</label>
            <input type="date" name="field_dob" id="field_dob"
              class="form-control" required
              max="<?= date('Y-m-d', strtotime('-13 years')) ?>">
            <div class="field-error">Introduce tu fecha de nacimiento</div>
          </div>

          <div class="form-group">
            <label class="form-label">Géneros preferidos * <span class="form-hint">(elige uno o más)</span></label>
            <div class="genre-chips" id="genre-chips">
              <?php foreach ([
                'NUEVO POP'                            => 'Nuevo Pop',
                'POP ESPAÑOL'                          => 'Pop Español',
                'POP INGLES'                           => 'Pop Inglés',
                'LATINO / REGGAETON'                   => 'Latino / Reggaeton',
                'URBANO / TRAP ANGLO'                  => 'Urbano / Trap Anglo',
                'URBANO / TRAP LOCAL'                  => 'Urbano / Trap Local',
                'INDIE/ROCK ESPAÑOL E INTERNACIONAL'   => 'Indie / Rock',
                'ELECTRÓNICA & DANCE'                  => 'Electrónica & Dance',
              ] as $val => $label): ?>
                <label class="genre-chip">
                  <input type="checkbox" name="field_genres" value="<?= htmlspecialchars($val) ?>">
                  <span><?= htmlspecialchars($label) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="field-error" id="genre-error">Selecciona al menos un género</div>
          </div>

          <p class="form-footnote">* Campo requerido</p>

          <div class="form-group form-group-submit">
            <button type="submit" class="btn btn-primary btn-lg" id="sorteo-submit">Participar en el sorteo</button>
            <span id="sorteo-status"></span>
          </div>
        </form>
      </div>

      <div id="sorteo-thanks" hidden>
        <div class="sorteo-success">
          <div class="sorteo-success-icon">&#127881;</div>
          <h2>¡Gracias por participar!</h2>
          <p>Una vez se realice el sorteo, si resultas ganador nos pondremos en contacto contigo. ¡Suerte!</p>
          <a href="/" class="btn btn-secondary">Volver al inicio</a>
        </div>
      </div>

    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  var form      = document.getElementById('sorteo-form');
  var submitBtn = document.getElementById('sorteo-submit');
  var statusEl  = document.getElementById('sorteo-status');
  var formWrap  = document.getElementById('sorteo-form-wrap');
  var thanksEl  = document.getElementById('sorteo-thanks');

  if (!form) return;

  function showFieldError(el, show) {
    var group = el.closest('.form-group');
    if (!group) return;
    group.classList.toggle('has-error', show);
  }

  function validate() {
    var ok = true;

    ['field_first_name', 'field_last_name', 'field_city', 'field_gender',
     'field_country_region', 'field_dob'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      var invalid = !el.value.trim();
      showFieldError(el, invalid);
      if (invalid) ok = false;
    });

    var checked = form.querySelectorAll('input[name="field_genres"]:checked');
    var genreErr = document.getElementById('genre-error');
    var chipsWrap = document.getElementById('genre-chips');
    var noGenre = checked.length === 0;
    if (chipsWrap) chipsWrap.classList.toggle('has-error', noGenre);
    if (genreErr)  genreErr.style.display = noGenre ? '' : 'none';
    if (noGenre) ok = false;

    return ok;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!validate()) return;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando…';
    statusEl.textContent = '';
    statusEl.className = '';

    var data = new FormData(form);

    fetch('https://subs.sonymusicfans.com/submit', {
      method: 'POST',
      body: data,
      credentials: 'omit',
    })
    .then(function (resp) {
      if (!resp.ok) throw new Error('HTTP ' + resp.status);
      return resp.json();
    })
    .then(function () {
      formWrap.hidden = true;
      thanksEl.hidden = false;
    })
    .catch(function () {
      statusEl.textContent = 'Ha ocurrido un error. Por favor, inténtalo más tarde.';
      statusEl.className = 'error';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Participar en el sorteo';
    });
  });
})();
</script>

<?php render_footer(); ?>
