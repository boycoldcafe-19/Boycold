# Google Sign-up Setup

1. Open Google Cloud Console and create or select a project.
2. Configure the OAuth consent screen and add the app's support email.
3. Create an OAuth 2.0 Web application client.
4. Add this exact authorized redirect URI:

   `http://localhost/boycoldv2/boycoldv2/google_callback.php`

5. Copy `.env.example` to `.env` in the project root and replace the placeholder values:

   - `GOOGLE_CLIENT_ID`: the OAuth client ID
   - `GOOGLE_CLIENT_SECRET`: the OAuth client secret
   - `GOOGLE_REDIRECT_URI`: the redirect URI above

   The `.env` file is ignored by Git. Restart Apache after creating or changing it.

The registration page starts at `google_signup.php`. The callback validates the OAuth state and Google's verified email before creating a user. Existing local accounts with the same email are not automatically linked.