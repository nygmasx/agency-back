<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Code de connexion</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">Votre code de connexion</h1>
    </div>

    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Bonjour,</p>

        <p>Vous avez demandé un code de connexion pour accéder au portail client <strong>{{ $clientName }}</strong>.</p>

        <div style="background: white; border: 2px dashed #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 30px 0;">
            <p style="margin: 0 0 10px 0; color: #666;">Votre code de connexion :</p>
            <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #667eea;">{{ $code }}</div>
        </div>

        <p style="color: #666; font-size: 14px;">Ce code expire dans <strong>10 minutes</strong>.</p>

        <p style="color: #666; font-size: 14px;">Si vous n'avez pas demandé ce code, vous pouvez ignorer cet email.</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="color: #999; font-size: 12px; text-align: center;">
            Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
        </p>
    </div>
</body>
</html>
