document.addEventListener('DOMContentLoaded', function() {
    var tipoSolicitanteSelect = document.querySelector('select[name="tipo_solicitante"]');
    var personaNaturalFields = document.getElementById('persona_natural_fields');
    var personaJuridicaFields = document.getElementById('persona_juridica_fields');
    var tipoPersonaJuridicaSelect = document.getElementById('tipo_persona_juridica');
    var representanteLegalFields = document.getElementById('representante_legal_fields');
    var apoderadoFields = document.getElementById('apoderado_fields');
    var autorizadoJuntaFields = document.getElementById('autorizado_junta_fields');


    tipoSolicitanteSelect.addEventListener('change', function() {
        var selectedOption = tipoSolicitanteSelect.options[tipoSolicitanteSelect.selectedIndex].value;

        if (selectedOption === 'persona_natural') {
            personaNaturalFields.style.display = 'block';
            personaJuridicaFields.style.display = 'none';
        } else if (selectedOption === 'persona_juridica') {
            personaNaturalFields.style.display = 'none';
            personaJuridicaFields.style.display = 'block';
        }
    });

    tipoPersonaJuridicaSelect.addEventListener('change', function() {
        var selectedOption = tipoPersonaJuridicaSelect.options[tipoPersonaJuridicaSelect.selectedIndex].value;

        if (selectedOption === 'representante_legal') {
            representanteLegalFields.style.display = 'block';
            apoderadoFields.style.display = 'none';
            autorizadoJuntaFields.style.display = 'none';
        } else if (selectedOption === 'apoderado') {
            representanteLegalFields.style.display = 'none';
            apoderadoFields.style.display = 'block';
            autorizadoJuntaFields.style.display = 'none';
        } else if (selectedOption === 'autorizado_junta') {
            representanteLegalFields.style.display = 'none';
            apoderadoFields.style.display = 'none';
            autorizadoJuntaFields.style.display = 'block';
        } else {
            representanteLegalFields.style.display = 'none';
            apoderadoFields.style.display = 'none';
            autorizadoJuntaFields.style.display = 'none';
        }
    });
});



