import { defineStore } from 'pinia'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

function capitalizePathSegment(segment: string) {
  return segment.charAt(0).toUpperCase() + segment.slice(1)
}

export const useBreadcrumbStore = defineStore('breadcrumb', () => {
  const route = useRoute()

  const breadcrumbs = computed(() => {
    const paths = decodeURIComponent(route.path)
      .split('/')
      .filter((p) => p)
    const crumbs = [{ name: 'COOPOLIS', path: '/' }]

    let buildPath = ''
    paths.forEach((path) => {
      if (path === "services") {
        buildPath += `/site`
      }
      else
        buildPath += `/${path}`
      crumbs.push({
        name: capitalizePathSegment(path),
        path: buildPath,
      })
      if (path === 'services') {
        buildPath = buildPath.replace('/site', '/services')
      }

    })



    return crumbs
  })
  return { breadcrumbs }
})

export const Title = defineStore('title', () => {
  const route = useRoute()
  const t = computed(() => {
    const section_id =
      typeof route.query.section_id === 'string' ? decodeURIComponent(route.query.section_id) : ''
    const store_id = route.query.store_id
    if (store_id) {
      return typeof store_id === 'string' ? capitalizePathSegment(store_id) : ''
    }
    if (section_id) {
      const s = decodeURIComponent(section_id).split('/')
      return capitalizePathSegment(s[s.length - 1])
    }
    const second = decodeURIComponent(route.path).split('/')
    return capitalizePathSegment(decodeURIComponent(second[second.length - 1]))
  })
  return { t }
})
export const Path = defineStore('infoBlock', () => {
  const route = useRoute()
  const info = computed(() => {
    const fullInfo = {
      service: route.query.block_id,
      sectionId:
        route.query.section_id !== undefined
          ? decodeURIComponent(
              typeof route.query.section_id === 'string' ? route.query.section_id : '',
            )
          : '',
      block_id:
        route.query.store_id !== undefined
          ? decodeURIComponent(typeof route.query.store_id === 'string' ? route.query.store_id : '')
          : '',
      store_number:
        route.query.store_number !== undefined
          ? decodeURIComponent(
              typeof route.query.store_number === 'string' ? route.query.store_number : '',
            )
          : '',
    }

    return fullInfo
  })
  return { info }
})
