import ResourcePage from '../components/ResourcePage'

export default function Chill() {
  return (
    <ResourcePage
      page="chill"
      title="Gestion du stress / Sophrologie"
      description="Retrouvez ici une sélection de ressources pour vous détendre et prendre soin de vous."
      forumLinks={[
        { link: '/detente-forums', text: 'Aller vers les forums détente' },
        { link: '/cafe_des_lumieres-forums', text: 'Aller vers le café des lumières' }
      ]}
    />
  )
}
