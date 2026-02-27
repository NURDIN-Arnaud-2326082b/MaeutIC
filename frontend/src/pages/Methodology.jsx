import ResourcePage from '../components/ResourcePage'

export default function Methodology() {
  return (
    <ResourcePage
      page="methodology"
      title="Méthodologie"
      description="Retrouvez ici une sélection de ressources pour vous aider à mieux comprendre la méthodologie de travail et les outils que nous utilisons."
      forumLinks={[
        { link: '/methodology-forums', text: 'Aller vers les forums méthodologie' }
      ]}
    />
  )
}
