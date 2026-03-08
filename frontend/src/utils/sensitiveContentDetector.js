// Patterns de détection de contenu sensible
export const warningPatterns = {
  suicide: {
    keywords: [
      'suicide', 'suicidaire', 'me tuer', 'en finir', 'mort', 'mourir', 'je veux mourir', 
      'plus envie de vivre', 'vie ne vaut plus la peine', 'suicider', 'passage à l\'acte',
      'idées noires', 'pensées suicidaires', 'envie de disparaître', 'fini ma vie',
      'plus la force', 'veux en finir', 'désespéré', 'désespoir total', 'souhaite mourir',
      'préférerais être mort', 'ça sert à rien', 'plus d\'espoir', 'sans issue'
    ],
    message: '💙 Besoin d\'aide ? Appelez le 3114 - Service national de prévention du suicide. Des professionnels sont là pour vous écouter 24h/24.',
    link: 'https://www.suicide-ecoute.fr/'
  },
  depression: {
    keywords: [
      'déprimé', 'dépression', 'tristesse', 'désespoir', 'vide', 'solitude', 'isolement', 
      'plus d\'énergie', 'cafard', 'mal être', 'blues', 'mélancolie', 'déprime',
      'baisse de moral', 'humeur dépressive', 'perte d\'intérêt', 'fatigue chronique',
      'crise existentielle', 'mal de vivre', 'détresse psychologique', 'désarroi',
      'abattement', 'accablement', 'découragement', 'démoralisation', 'perte d\'estime'
    ],
    message: '💚 Vous traversez une période difficile ? Contactez SOS Amitié au 09 72 39 40 50. Parler peut vous aider.',
    link: 'https://www.sos-amitie.com/'
  },
  harassment: {
    keywords: [
      'harcèlement', 'harcèle', 'intimidation', 'menace', 'cyberharcèlement', 'moquerie', 
      'humiliation', 'persécution', 'bullying', 'brimade', 'maltraitance', 'abus de pouvoir',
      'chantage', 'manipulation', 'emprise', 'stalking', 'harcèlement moral', 
      'harcèlement sexuel', 'harcèlement scolaire', 'harcèlement professionnel'
    ],
    message: '🛡️ Victime de harcèlement ? Appelez le 3020 (harcèlement scolaire), le 3018 (cyberharcèlement) ou le 3919 (violences femmes). Vous n\'êtes pas seul(e).',
    link: 'https://www.education.gouv.fr/non-au-harcelement'
  },
  violence: {
    keywords: [
      'violence', 'violent', 'agress', 'battre', 'frapper', 'abus', 'maltraitance', 
      'coups', 'brimade', 'agression', 'violence conjugale', 'violence domestique',
      'violence psychologique', 'violence verbale', 'violence physique', 'violence sexuelle',
      'viol', 'agression sexuelle', 'inceste', 'conjoint violent', 'partenaire violent'
    ],
    message: '🛡️ En cas de violence, appelez le 3919 (Violences Femmes Info) ou le 119 (Enfance en danger). Des aides existent.',
    link: 'https://arretonslesviolences.gouv.fr/'
  },
  addiction: {
    keywords: [
      'drogue', 'alcool', 'addiction', 'dépendance', 'sevrage', 'overdose', 'ivre', 
      'saoul', 'cuite', 'cannabis', 'héroïne', 'cocaïne', 'tabac', 'jeu', 
      'jeux d\'argent', 'substance', 'psychotrope', 'stupéfiant', 'toxicomanie', 
      'alcoolisme', 'binge drinking', 'défonce', 'manque', 'rechute'
    ],
    message: '🧠 Besoin d\'aide pour une addiction ? Appelez Drogues Info Service au 0 800 23 13 13 (appel gratuit), Alcool Info Service au 09 80 98 09 30, ou Joueurs Info Service au 09 74 75 13 13 (jeu d\'argent).',
    link: 'https://www.drogues-info-service.fr/'
  },
  eating_disorder: {
    keywords: [
      'anorexie', 'boulimie', 'tca', 'trouble alimentaire', 'je me prive de manger', 
      'crise de boulimie', 'hyperphagie', 'orthorexie', 'restriction alimentaire', 
      'jeûne', 'régime strict', 'calories', 'compter les calories', 'peur de grossir',
      'vomissements provoqués', 'laxatifs', 'pesée obsessionnelle', 'dénutrition'
    ],
    message: '🍎 Besoin d\'aide pour un trouble alimentaire ? Contactez Fil Santé Jeunes au 0 800 235 236.',
    link: 'https://www.filsantejeunes.com/'
  }
}

// Fonction pour normaliser le texte (supprimer les accents et mettre en minuscules)
const normalizeText = (str) => {
  if (!str) return ''
  return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase()
}

// Fonction principale de détection
export const checkSensitiveContent = (text) => {
  if (!text || text.trim().length === 0) return []
  
  const detectedWarnings = []
  const normalizedText = normalizeText(text)
  
  for (const [category, pattern] of Object.entries(warningPatterns)) {
    const hasMatch = pattern.keywords.some(keyword => {
      const normalizedKeyword = normalizeText(keyword)
      return normalizedText.includes(normalizedKeyword)
    })
    
    if (hasMatch && !detectedWarnings.some(w => w.message === pattern.message)) {
      detectedWarnings.push(pattern)
    }
  }
  
  return detectedWarnings
}
