export default function ForgotPassword() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
      <div className="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h1 className="text-2xl font-bold text-center mb-6">Mot de passe oublié</h1>
        <p className="text-gray-600 text-center mb-6">
          Entrez votre email pour recevoir un lien de réinitialisation
        </p>
        <form className="space-y-4">
          <input
            type="email"
            placeholder="Votre email"
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          />
          <button className="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Envoyer
          </button>
        </form>
      </div>
    </div>
  )
}
