@extends('layouts.public')

@section('titulo', 'Términos de servicio — NovaReef')

@section('contenido')

    @include('welcome.navbar')

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-24">

        <h1 class="font-editorial uppercase italic text-4xl sm:text-5xl font-extrabold text-slate-900 mb-2 leading-[0.95]">
            Términos de servicio
        </h1>
        <p class="text-slate-500 text-sm mb-4">
            Última actualización: {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </p>
        <p class="text-slate-600 leading-relaxed mb-12">
            Estos Términos de Servicio ("Términos") regulan el acceso y uso de la plataforma
            NovaReef ("la Plataforma", "el Servicio") por parte de colegios de árbitros de fútbol
            y sus usuarios autorizados. Al crear una cuenta, acceder o usar el Servicio, el colegio
            y cada uno de sus usuarios aceptan quedar obligados por estos Términos y por la
            <a href="{{ route('privacidad.politica') }}" class="text-blue-600 hover:underline">Política de tratamiento de datos</a>,
            que forma parte integral de este acuerdo. Si no estás de acuerdo con alguna parte de
            estos Términos, no debes usar el Servicio.
        </p>

        <div class="space-y-9 text-slate-600 leading-relaxed">

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">1. Definiciones</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li><strong>NovaReef:</strong> la plataforma de software y quien la desarrolla y opera.</li>
                    <li><strong>Colegio:</strong> la asociación o colegio de árbitros que contrata el Servicio, identificado en la plataforma con su propia cuenta y subdominio.</li>
                    <li><strong>Usuario:</strong> cualquier persona que accede al Servicio bajo una cuenta de un Colegio, sin importar su rol (ejecutivo, tesorero, designador, técnico, sanciones o árbitro).</li>
                    <li><strong>Cuenta:</strong> el acceso individual, con credenciales propias, asignado a un Usuario dentro de un Colegio.</li>
                    <li><strong>Contenido del Colegio:</strong> toda la información que el Colegio o sus Usuarios registran en la Plataforma (datos de árbitros, designaciones, movimientos financieros, sanciones, documentos, etc.).</li>
                    <li><strong>Plan:</strong> el nivel de servicio contratado (funcionalidades, límites de cuentas y de árbitros) y su precio asociado.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">2. Descripción del servicio</h2>
                <p>NovaReef es una plataforma de software como servicio (SaaS), de arquitectura
                multi-tenant, que presta herramientas de gestión administrativa a colegios de
                árbitros de fútbol: registro y perfil de árbitros, designaciones y disponibilidad,
                gestión de torneos, control financiero interno del Colegio, formación académica y
                registro disciplinario. El Servicio se presta "tal cual" ("as is"), sujeto a las
                funcionalidades incluidas en el Plan contratado por cada Colegio.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">3. NovaReef no es</h2>
                <p>NovaReef no es una federación, liga ni autoridad deportiva. No certifica
                árbitros, no organiza torneos y no tiene afiliación con la FCF,
                DIMAYOR, FIFA ni ninguna otra entidad rectora del fútbol. Las decisiones deportivas,
                disciplinarias y reglamentarias que un Colegio registra en la Plataforma
                (designaciones, sanciones, calificaciones, resultados) son responsabilidad
                exclusiva del Colegio: <strong>NovaReef únicamente provee la herramienta técnica
                donde esa información queda registrada</strong>, y no participa en, ni valida, ni
                respalda el contenido de esas decisiones.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">4. Cuentas, elegibilidad y responsabilidad del usuario</h2>
                <p>Para contratar el Servicio, el Colegio debe estar legalmente constituido o
                actuar a través de una persona con capacidad para representarlo. Cada Colegio es
                responsable de:</p>
                <ul class="list-disc pl-5 space-y-1.5 mt-2">
                    <li>La exactitud y legalidad de la información que registra en la Plataforma.</li>
                    <li>Mantener la confidencialidad de las credenciales de todas sus Cuentas y notificar a NovaReef ante cualquier uso no autorizado.</li>
                    <li>El uso que sus Usuarios (ejecutivo, tesorero, designador, técnico, sanciones, árbitros) le den al Servicio, incluidas las acciones realizadas bajo sus credenciales.</li>
                    <li>Asignar los roles y permisos internos de forma diligente, acorde a la función real de cada Usuario.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">5. Uso aceptable</h2>
                <p>El Servicio debe usarse únicamente para la operación legítima de un colegio de
                árbitros de fútbol. Sin perjuicio de otras restricciones aplicables, no está
                permitido:</p>
                <ul class="list-disc pl-5 space-y-1.5 mt-2">
                    <li>Registrar información falsa a sabiendas o suplantar la identidad de un tercero.</li>
                    <li>Intentar vulnerar, escanear o comprometer la seguridad, disponibilidad o integridad de la Plataforma.</li>
                    <li>Acceder o intentar acceder a datos de otro Colegio sin autorización.</li>
                    <li>Usar el Servicio para fines distintos a la gestión de un colegio de árbitros, o de forma que infrinja la ley colombiana.</li>
                    <li>Realizar ingeniería inversa, descompilar o revender el Servicio sin autorización expresa y por escrito de NovaReef.</li>
                </ul>
                <p class="mt-2">El incumplimiento de este numeral puede dar lugar a la suspensión o
                terminación de la Cuenta, conforme al numeral 10.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">6. Planes, pagos y facturación</h2>
                <p>El acceso al Servicio requiere una suscripción vigente a alguno de los Planes
                disponibles. El precio, periodicidad y alcance de cada Plan se informan al momento
                de la contratación. Actualmente, el cobro de la suscripción entre NovaReef y el
                Colegio se gestiona de forma manual (sin cobro automático a tarjetas o cuentas
                bancarias); cualquier cambio hacia un método de cobro automatizado será informado
                con antelación. El Colegio puede solicitar el cambio de Plan en cualquier momento;
                la cancelación de la suscripción no interrumpe el acceso de forma inmediata —
                el Colegio conserva el Servicio hasta el vencimiento del período ya pagado.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">7. Propiedad intelectual</h2>
                <p>El software, diseño, marca, logotipos y demás elementos de la Plataforma son
                propiedad de NovaReef o de sus licenciantes, y están protegidos por la normativa
                de propiedad intelectual aplicable. Estos Términos no transfieren al Colegio ni a
                sus Usuarios ningún derecho de propiedad sobre el software — únicamente se concede
                una licencia limitada, no exclusiva e intransferible para usar el Servicio conforme
                al Plan contratado.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">8. Contenido y datos del Colegio</h2>
                <p>El Contenido del Colegio sigue siendo propiedad del Colegio. NovaReef no reclama
                propiedad sobre esa información y solo la trata en su calidad de encargado técnico,
                conforme a la <a href="{{ route('privacidad.politica') }}" class="text-blue-600 hover:underline">Política de tratamiento de datos</a>.
                Ante la terminación de la relación contractual, el Colegio puede solicitar la
                exportación de su Contenido dentro de un plazo razonable antes de que se elimine
                de los sistemas activos de NovaReef.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">9. Disponibilidad, soporte y cambios al servicio</h2>
                <p>NovaReef procura mantener la Plataforma disponible de forma continua, <b>pero no
                garantiza un funcionamiento ininterrumpido o libre de errores.</B> El Servicio puede
                presentar interrupciones por mantenimiento programado, actualizaciones o fallas
                técnicas ajenas a un control razonable. NovaReef puede modificar, mejorar o
                descontinuar funcionalidades del Servicio, procurando no afectar de forma
                sustancial las capacidades incluidas en el Plan vigente del Colegio sin previo aviso.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">10. Suspensión y terminación</h2>
                <p>NovaReef puede suspender o terminar el acceso de un Colegio o Usuario, con
                notificación previa cuando sea razonablemente posible, en casos como: incumplimiento
                del uso aceptable (numeral 5), impago de la suscripción tras el vencimiento del
                período pagado, o riesgo real para la seguridad de la Plataforma o de otros
                Colegios. El Colegio puede cancelar su suscripción en cualquier momento; el efecto
                de esa cancelación sobre el acceso se rige por el numeral 6.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">11. Limitación de responsabilidad</h2>
                <p>En la máxima medida permitida por la ley, NovaReef no será responsable por:
                decisiones deportivas, disciplinarias o reglamentarias tomadas por un Colegio;
                pérdidas económicas derivadas de esas decisiones; el contenido, exactitud o
                legalidad de la información que los Usuarios registran; ni por daños indirectos,
                incidentales o consecuentes derivados del uso del Servicio. La responsabilidad de
                NovaReef, cuando aplique, se limita a la correcta prestación del servicio técnico
                contratado.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">12. Indemnización</h2>
                <p>El Colegio se compromete a indemnizar a NovaReef frente a reclamos de terceros
                que surjan del Contenido del Colegio, del uso indebido del Servicio por parte de
                sus Usuarios, o del incumplimiento de estos Términos por parte del Colegio.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">13. Protección de datos personales</h2>
                <p>El tratamiento de datos personales realizado a través del Servicio se rige por
                la <a href="{{ route('privacidad.politica') }}" class="text-blue-600 hover:underline">Política de tratamiento de datos</a>,
                que forma parte integral de estos Términos y debe leerse en conjunto con ellos.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">14. Fuerza mayor</h2>
                <p>Ninguna de las partes será responsable por incumplimientos derivados de
                circunstancias razonablemente fuera de su control, incluyendo fallas de
                proveedores de infraestructura, cortes de energía o telecomunicaciones, desastres
                naturales o disposiciones de autoridad competente.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">15. Modificaciones a estos Términos</h2>
                <p>NovaReef puede actualizar estos Términos para reflejar cambios en el Servicio o
                en la normativa aplicable. Los cambios sustanciales se comunicarán a los Colegios
                con cuenta activa con antelación razonable. El uso continuado del Servicio después
                de la fecha de vigencia de una actualización constituye aceptación de los nuevos
                Términos.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">16. Ley aplicable y jurisdicción</h2>
                <p>Estos Términos se rigen por las leyes de la República de Colombia. Cualquier
                controversia derivada de estos Términos se someterá a los jueces competentes de
                Colombia, sin perjuicio de que las partes acuerden un mecanismo alternativo de
                resolución de conflictos.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">17. Disposiciones generales</h2>
                <p>Si alguna disposición de estos Términos se considera inválida o inaplicable, el
                resto conserva su validez. Estos Términos, junto con la Política de tratamiento de
                datos, constituyen el acuerdo completo entre el Colegio y NovaReef respecto del
                Servicio. El Colegio no puede ceder sus derechos u obligaciones bajo estos Términos
                sin el consentimiento previo de NovaReef.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900 mb-2">18. Contacto</h2>
                <p>Para dudas sobre estos Términos, escríbenos a
                <a href="mailto:contacto@novareef.com" class="text-blue-600 hover:underline">contacto@novareef.com</a>.</p>
            </section>

        </div>

    </div>

    @include('welcome.footer')

@endsection
