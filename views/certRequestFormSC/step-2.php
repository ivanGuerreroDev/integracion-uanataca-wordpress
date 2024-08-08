<form class="form-certificado" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300&display=swap" rel="stylesheet">

    <input type="hidden" name="action" value="guardar_informacion_certificado">
    <input type="hidden" name="order_id" value="<?php echo esc_attr($_GET['order_id']); ?>">

    <label for="tipo_solicitante">Tipo de Solicitante:</label>
    <select name="applicant_type" id="tipo_solicitante" required>
        <option value="persona_natural">Persona Natural</option>
        <option value="persona_juridica">Persona Jurídica</option>
    </select>

    <label for="vigencia_certificado">Vigencia del Certificado:</label>
    <select name="certificate_validity" id="vigencia_certificado" required>
        <option value="1">1 año</option>
        <option value="2">2 años</option>
    </select>

    <label for="nombre_pac">Nombre del PAC:</label>
    <select name="pac_name" id="nombre_pac" required>
        <option value="pac1">PAC 1</option>
        <option value="pac2">PAC 2</option>
        <option value="pac3">PAC 3</option>
    </select>

    <!-- Campos específicos para Persona Natural -->
    <div id="persona_natural_fields">
        <label for="adjunto_identificacion">Adjuntar cédula o pasaporte del solicitante:</label>
        <input type="file" name="adjunto_identificacion" accept=".pdf, .jpg, .png">

        <label for="telefono_solicitante">Teléfono del solicitante (con código de país):</label>
        <input type="tel" name="applicant_phone" pattern="[0-9]{1,4}-[0-9]{1,12}" placeholder="Ejemplo: 1234-567890" required>

        <label for="correo_solicitante">Correo electrónico del solicitante:</label>
        <input type="email" placeholder="example@correo.com" name="applicant_email" required>
    </div>

    <!-- Campos específicos para Persona Jurídica -->
    <div id="persona_juridica_fields" style="display: none;">
        <label for="tipo_persona_juridica">Tipo de Persona Jurídica:</label>
        <select name="tipo_persona_juridica" id="tipo_persona_juridica" required>
            <option value="representante_legal">Representante Legal es solicitante</option>
            <option value="apoderado">El solicitante es un apoderado con facultades</option>
            <option value="autorizado_junta">El solicitante está autorizado por la junta directiva</option>
        </select>

        <div id="representante_legal_fields" style="display: none;">
            <!-- Campos para Representante Legal -->
            <label for="adjunto_identificacion_representante">Adjuntar cédula o pasaporte del solicitante:</label>
            <input type="file" name="adjunto_identificacion_representante" accept=".pdf, .jpg, .png" required>

            <label for="adjunto_pacto_social">Adjuntar copia del Pacto social o certificado de registro público:</label>
            <input type="file" name="adjunto_pacto_social" accept=".pdf, .jpg, .png" required>

            <label for="adjunto_escritura_representante">Adjuntar escritura del último cambio de la junta
                directiva:</label>
            <input type="file" name="adjunto_escritura_representante" accept=".pdf, .jpg, .png" required>

            <label for="telefono_representante">Teléfono del solicitante (con código de país):</label>
            <input type="tel" name="telefono_representante" pattern="[0-9]{1,4}-[0-9]{1,12}" placeholder="Ejemplo: 1234-567890" required>

            <label for="correo_representante">Correo electrónico del solicitante:</label>
            <input type="email" name="correo_representante" required>
        </div>

        <div id="apoderado_fields" style="display: none;">
            <!-- Campos para Apoderado -->
            <label for="adjunto_identificacion_apoderado">Adjuntar cédula o pasaporte del solicitante:</label>
            <input type="file" name="adjunto_identificacion_apoderado" accept=".pdf, .jpg, .png" required>

            <label for="adjunto_escritura_apoderado">Adjuntar copia de la escritura que establece las
                facultades:</label>
            <input type="file" name="adjunto_escritura_apoderado" accept=".pdf, .jpg, .png" required>

            <label for="telefono_apoderado">Teléfono del solicitante (con código de país):</label>
            <input type="tel" name="telefono_apoderado" pattern="[0-9]{1,4}-[0-9]{1,12}" placeholder="Ejemplo: 1234-567890" required>

            <label for="correo_apoderado">Correo electrónico del solicitante:</label>
            <input type="email" name="correo_apoderado" required>
        </div>

        <div id="autorizado_junta_fields" style="display: none;">
            <!-- Campos para Autorizado por Junta Directiva -->
            <label for="adjunto_identificacion_autorizado">Adjuntar cédula o pasaporte del solicitante:</label>
            <input type="file" name="adjunto_identificacion_autorizado" accept=".pdf, .jpg, .png" required>

            <label for="adjunto_identificacion_junta">Adjuntar cédula o pasaporte de los miembros de la junta
                directiva:</label>
            <input type="file" name="adjunto_identificacion_junta" accept=".pdf, .jpg, .png" multiple required>

            <label for="adjunto_pacto_social_autorizado">Adjuntar Pacto Social o certificado de registro
                público:</label>
            <input type="file" name="adjunto_pacto_social_autorizado" accept=".pdf, .jpg, .png" required>

            <label for="adjunto_escritura_autorizado">Adjuntar escritura del último cambio de la junta
                directiva:</label>
            <input type="file" name="adjunto_escritura_autorizado" accept=".pdf, .jpg, .png" required>

            <label for="adjunto_acta_junta">Acta de Junta directiva autorizando al solicitante a suscribir contrato y
                solicitar certificado de factura electrónica para su empresa:</label>
            <input type="file" name="adjunto_acta_junta" accept=".pdf, .jpg, .png" required>

            <label for="telefono_autorizado">Teléfono del solicitante (con código de país):</label>
            <input type="tel" name="telefono_autorizado" pattern="[0-9]{1,4}-[0-9]{1,12}" placeholder="Ejemplo: 1234-567890" required>

            <label for="correo_autorizado">Correo electrónico del solicitante:</label>
            <input type="email" name="correo_autorizado" required>
        </div>

    </div>

    <input type="submit" name="registro" value="Guardar Informacion Certificado">
</form>