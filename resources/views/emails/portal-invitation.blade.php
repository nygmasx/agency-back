<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation au portail client</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">Invitation au portail client</h1>
    </div>

    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Bonjour {{ $collaboratorName }},</p>

        <p>Vous avez été invité(e) à accéder au portail client de <strong>{{ $clientName }}</strong>.</p>

        <p>Ce portail vous permet de :</p>
        <ul style="color: #666;">
            <li>Suivre l'avancement de vos projets</li>
            <li>Consulter et commenter les tâches</li>
            <li>Échanger avec l'équipe</li>
            <li>Partager des fichiers</li>
        </ul>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $loginUrl }}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 15px 30px; border-radius: 5px; font-weight: bold;">
                Accéder au portail
            </a>
        </div>

        @if($accessType === 'email')
        <p style="color: #666; font-size: 14px; text-align: center;">
            Vous pourrez vous connecter avec votre adresse email.<br>
            Un code de vérification vous sera envoyé à chaque connexion.
        </p>
        @endif

        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="color: #999; font-size: 12px; text-align: center;">
            Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
        </p>
    </div>
</body>
</html>
