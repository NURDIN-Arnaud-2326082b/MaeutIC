import ResourcePage from '../components/ResourcePage'

export default function Administrative() {
  return (
    <ResourcePage
      page="administrative"
      title="Administratif"
      description="Retrouvez ici une sélection de ressources pour vous aider avec tout ce qui concerne l'administratif, les démarches et les outils que nous utilisons."
      forumLinks={[
        { link: '/administratif-forums', text: 'Aller vers les forums administratifs' }
      ]}
    />
  )
}
