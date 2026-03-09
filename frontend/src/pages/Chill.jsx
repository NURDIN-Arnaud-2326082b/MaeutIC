import ResourcePage from '../components/ResourcePage'

export default function Chill() {
  return (
    <ResourcePage
      page="chill"
      title="Gestion du stress / Sophrologie"
      description="Retrouvez ici une sélection de ressources pour vous détendre et prendre soin de vous."
      forumLinks={[
        { link: '/detente-forums', text: 'Aller vers les forums détente' }
      ]}
    />
  )
}
