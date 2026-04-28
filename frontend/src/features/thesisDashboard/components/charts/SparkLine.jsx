import { BarChart, Bar, Tooltip, ResponsiveContainer } from 'recharts'

export default function SparkLine({ data }) {
  return (
    <ResponsiveContainer width="100%" height={80}>
      <BarChart data={data} margin={{ top: 4, right: 0, bottom: 0, left: 0 }} barCategoryGap="20%">
        <Tooltip
          contentStyle={{ fontSize: 11, padding: '2px 8px', border: 'none', background: '#1e293b', color: '#fff', borderRadius: 6 }}
          itemStyle={{ color: '#fff' }}
          formatter={(v) => [v.toLocaleString('fr-FR'), 'thèses']}
          labelFormatter={(l) => `${l}`}
        />
        <Bar dataKey="nb" fill="#016d76" radius={[4, 4, 0, 0]} />
      </BarChart>
    </ResponsiveContainer>
  )
}
