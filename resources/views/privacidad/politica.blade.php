@extends('layouts.public')

@section('titulo', 'Política de tratamiento de datos — NovaReef')

@section('contenido')

    @include('welcome.navbar')

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-24">

        <h1 class="font-editorial uppercase italic text-4xl sm:text-5xl font-extrabold text-slate-900 mb-2 leading-[0.95]">
            Política de tratamiento de datos
        </h1>
        <p class="text-slate-500 text-sm mb-4">
            Versión {{ \App\Services\PoliticaPrivacidadService::VERSION_ACTUAL }} ·
            Ley 1581 de 2012, Decreto 1377 de 2013 y demás normas que los modifiquen o reglamenten.
        </p>
        <p class="text-slate-600 leading-relaxed mb-12">
            Esta Política de Tratamiento de Datos Personales ("Política") describe cómo NovaReef
            recolecta, usa, almacena y protege los datos personales de quienes usan la Plataforma,
            en cumplimiento de la Ley 1581 de 2012 y el Decreto 1377 de 2013 de la República de
            Colombia. Forma parte integral de los
            <a href="{{ route('legal.terminos') }}" class="text-blue-600 hover:underline">Términos de servicio</a>
            y debe leerse en conjunto con ellos.
        </p>

        <div class="space-y-9 text-slate-600 leading-relaxed">

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">1. Definiciones</h2>
                <p>Para efectos de esta Política, y conforme al artículo 3 de la Ley 1581 de 2012:</p>
                <ul class="list-disc pl-5 space-y-1.5 mt-2">
                    <li><strong>Dato personal:</strong> cualquier información vinculada o que pueda asociarse a una o varias personas naturales determinadas o determinables.</li>
                    <li><strong>Dato sensible:</strong> dato personal que afecta la intimidad del titular o cuyo uso indebido puede generar discriminación (entre otros, datos de salud). Su tratamiento exige autorización explícita y separada.</li>
                    <li><strong>Titular:</strong> la persona natural cuyos datos personales son objeto de tratamiento (por ejemplo, un árbitro o un usuario administrativo del Colegio).</li>
                    <li><strong>Tratamiento:</strong> cualquier operación sobre datos personales, como recolección, almacenamiento, uso, circulación o supresión.</li>
                    <li><strong>Responsable del tratamiento:</strong> quien decide sobre la base de datos o el tratamiento de los datos.</li>
                    <li><strong>Encargado del tratamiento:</strong> quien realiza el tratamiento de datos personales por cuenta del Responsable.</li>
                    <li><strong>Autorización:</strong> el consentimiento previo, expreso e informado del Titular para el tratamiento de sus datos.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">2. Principios que rigen el tratamiento</h2>
                <p>NovaReef trata los datos personales conforme a los principios del artículo 4 de la
                Ley 1581/2012: legalidad, finalidad, libertad, veracidad o calidad, transparencia,
                acceso y circulación restringida, seguridad y confidencialidad. En particular, los
                datos solo se usan para las finalidades descritas en esta Política, no se obtienen
                ni se transfieren sin autorización del Titular, y solo pueden ser conocidos por
                personas autorizadas dentro del Colegio correspondiente.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">3. Responsable y encargado del tratamiento</h2>
                <p>Cada Colegio es <strong>Responsable</strong> del tratamiento de los datos personales
                de sus árbitros y demás usuarios: decide qué información se recolecta a través de la
                Plataforma y para qué la usa en su operación interna. NovaReef actúa como
                <strong>Encargado</strong> del tratamiento: procesa esos datos por cuenta del Colegio,
                únicamente para prestar el servicio técnico contratado, y no los usa para fines
                propios ajenos a esa prestación.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">4. Datos que se recolectan</h2>
                <p>Según el rol del usuario dentro del Colegio, la Plataforma puede tratar:</p>
                <ul class="list-disc pl-5 space-y-1.5 mt-2">
                    <li><strong>Datos de identificación:</strong> nombre, tipo y número de documento, lugar de expedición.</li>
                    <li><strong>Datos de contacto:</strong> correo electrónico, teléfono, dirección, barrio.</li>
                    <li><strong>Datos profesionales/deportivos:</strong> categoría, disponibilidad, historial de designaciones y partidos, calificaciones, sanciones disciplinarias.</li>
                    <li><strong>Datos financieros:</strong> pagos, saldos, cuotas y multas registrados dentro del Colegio.</li>
                    <li><strong>Datos académicos:</strong> asistencia y participación en sesiones de formación.</li>
                    <li><strong>Datos sensibles de salud</strong> (opcionales): grupo sanguíneo (RH) y afiliación a EPS — ver numeral 6.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">5. Finalidad del tratamiento</h2>
                <p>Los datos se usan exclusivamente para operar el Colegio dentro de la Plataforma:
                gestionar árbitros y su disponibilidad, designar partidos, administrar torneos,
                registrar movimientos financieros internos del Colegio, llevar el control académico
                y disciplinario, y comunicarse con los usuarios sobre asuntos relacionados con el
                Servicio. NovaReef no vende los datos personales ni los comparte con terceros para
                fines comerciales ajenos a este propósito.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">6. Datos sensibles</h2>
                <p>El grupo sanguíneo y la afiliación a EPS son datos sensibles de salud (art. 5,
                Ley 1581/2012). Son <strong>opcionales</strong>: el Titular no está obligado a
                suministrarlos, y la Plataforma exige una autorización explícita y separada de la
                aceptación general de esta Política antes de guardarlos. Ningún Titular sufre
                consecuencias adversas en el Servicio por no proporcionar estos datos.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">7. Menores de edad</h2>
                <p>La Plataforma no está diseñada para recolectar ni verificar la edad de los
                Titulares. Si un Colegio identifica que alguno de sus árbitros es menor de edad, es
                responsabilidad del Colegio obtener la autorización de su representante legal antes
                de registrar sus datos en la Plataforma, garantizar el respeto de su interés
                superior y evitar el tratamiento de datos sensibles del menor, conforme al
                artículo 7 de la Ley 1581/2012.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">8. Autorización del titular</h2>
                <p>La autorización general se obtiene al momento en que el usuario acepta esta
                Política dentro de la Plataforma, mediante una casilla de verificación que no viene
                marcada por defecto. El tratamiento de datos sensibles (numeral 6) requiere una
                autorización adicional e independiente, obtenida en el formulario donde se
                registran esos campos. El Titular puede consultar en cualquier momento si otorgó
                estas autorizaciones y revocarlas conforme al numeral 12.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">9. Encargados externos y transferencia de datos</h2>
                <p>NovaReef utiliza proveedores de infraestructura tecnológica (hosting y envío de
                correo electrónico transaccional) para prestar el Servicio. Estos proveedores
                actúan como encargados subordinados: solo procesan los datos necesarios para su
                función técnica y no los usan con fines propios. Algunos de estos proveedores
                pueden operar servidores fuera de Colombia, incluido Estados Unidos; en esos casos,
                NovaReef procura contratar con proveedores que ofrezcan garantías adecuadas de
                protección de datos.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">10. Seguridad de la información</h2>
                <p>NovaReef aplica medidas técnicas y administrativas razonables para proteger los
                datos personales: acceso a la información restringido por rol dentro de cada
                Colegio, aislamiento de datos entre colegios (arquitectura multi-tenant), doble
                factor de autenticación para cuentas administrativas de alto privilegio, contraseñas
                almacenadas de forma cifrada, y registro de auditoría de las acciones administrativas
                más sensibles. Ninguna medida de seguridad es infalible; ante un incidente que
                comprometa datos personales, NovaReef lo gestionará conforme a la normativa aplicable.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">11. Cookies y tecnologías similares</h2>
                <p>La Plataforma usa únicamente una cookie de sesión, estrictamente necesaria para
                mantener la sesión iniciada del usuario mientras usa el Servicio. NovaReef no usa
                cookies de rastreo publicitario ni comparte esta información con redes de publicidad
                de terceros.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">12. Derechos del titular (ARCO)</h2>
                <p>Como Titular de los datos, tienes derecho a:</p>
                <ul class="list-disc pl-5 space-y-1.5 mt-2">
                    <li><strong>Acceder</strong> a tus datos personales, de forma gratuita.</li>
                    <li><strong>Rectificarlos</strong> cuando sean inexactos o estén desactualizados.</li>
                    <li>Solicitar su <strong>Cancelación</strong> cuando el tratamiento no respete los principios y deberes de la ley.</li>
                    <li><strong>Oponerte</strong> a un tratamiento específico, o revocar la autorización otorgada.</li>
                    <li>Ser informado sobre el uso que se ha dado a tus datos.</li>
                    <li>Presentar quejas ante la Superintendencia de Industria y Comercio.</li>
                </ul>
                <p class="mt-2">Si ya eres usuario, puedes ejercer estos derechos desde
                "Mis datos personales" en tu panel; si no tienes cuenta, escríbenos a
                <a href="mailto:contacto@novareef.com" class="text-blue-600 hover:underline">contacto@novareef.com</a>.
                Las consultas se atienden en un plazo máximo de diez (10) días hábiles desde su
                recepción, y los reclamos en quince (15) días hábiles; si no es posible atenderlos
                en ese plazo, se informará al Titular el motivo de la demora antes de su
                vencimiento, con una prórroga máxima de ocho (8) días hábiles adicionales para
                consultas.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">13. Vigencia y conservación de los datos</h2>
                <p>Los datos personales se conservan mientras exista la relación entre el Titular y
                el Colegio, y durante el tiempo adicional que exijan las obligaciones legales,
                contables o contractuales aplicables. Cumplido ese plazo, los datos se suprimen o
                anonimizan, salvo que exista una obligación legal de conservarlos por más tiempo.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">14. Modificaciones a esta política</h2>
                <p>Esta Política puede actualizarse para reflejar cambios normativos o en la forma
                en que NovaReef trata los datos personales. Cuando una actualización sea sustancial,
                se solicitará nuevamente la aceptación de los usuarios activos antes de continuar
                usando el Servicio, conforme al mecanismo de aceptación por versión ya incorporado
                en la Plataforma.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">15. Autoridad de control</h2>
                <p>La Superintendencia de Industria y Comercio (SIC) es la autoridad de protección
                de datos personales en Colombia. Si consideras que tus derechos no fueron atendidos
                adecuadamente por NovaReef o por el Colegio responsable, puedes presentar tu queja
                ante la SIC.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">16. Contacto</h2>
                <p>Para ejercer tus derechos o resolver dudas sobre esta Política, escríbenos a
                <a href="mailto:contacto@novareef.com" class="text-blue-600 hover:underline">contacto@novareef.com</a>.</p>
            </section>

        </div>

    </div>

    @include('welcome.footer')

@endsection
