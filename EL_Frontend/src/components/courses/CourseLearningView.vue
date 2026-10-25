<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api from '../../api/axios'

type Lesson = {
  id: number
  title: string
  content_type: 'video' | 'article'
  video_url?: string
  article_content?: string
  is_completed?: boolean
  completed?: boolean
}

type Section = {
  id: number
  title: string
  lessons: Lesson[]
}

type Course = {
  id: number
  title: string
  sections: Section[]
}

type Props = {
  course: Course
  currentLesson: Lesson
  allowProgressUpdate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  allowProgressUpdate: true,
})

const mainScrollRef = ref<HTMLElement | null>(null)
const videoIframeRef = ref<HTMLIFrameElement | null>(null)
const youtubePlayerRef = ref<any>(null)
const completedLessonIds = ref(new Set<number>())
const currentLessonCompleted = ref(false)

let youtubeApiPromise: Promise<void> | null = null

const parseVideoUrl = (videoUrl: string) => {
  if (!videoUrl) {
    return { provider: null as 'youtube' | 'vimeo' | null, embedUrl: '' }
  }

  let parsedUrl: URL

  try {
    parsedUrl = new URL(videoUrl)
  } catch {
    try {
      parsedUrl = new URL(`https://${videoUrl}`)
    } catch {
      return { provider: null as 'youtube' | 'vimeo' | null, embedUrl: videoUrl }
    }
  }

  const hostname = parsedUrl.hostname.toLowerCase()

  if (hostname.includes('youtube.com') || hostname.includes('youtu.be')) {
    let videoId = ''

    if (hostname.includes('youtu.be')) {
      videoId = parsedUrl.pathname.split('/').filter(Boolean)[0] ?? ''
    } else if (parsedUrl.pathname.startsWith('/embed/')) {
      videoId = parsedUrl.pathname.split('/')[2] ?? ''
    } else if (parsedUrl.pathname.startsWith('/shorts/')) {
      videoId = parsedUrl.pathname.split('/')[2] ?? ''
    } else {
      videoId = parsedUrl.searchParams.get('v') ?? ''
    }

    if (!videoId) {
      return { provider: null as 'youtube' | 'vimeo' | null, embedUrl: videoUrl }
    }

    const embedUrl = new URL(`https://www.youtube.com/embed/${videoId}`)
    embedUrl.searchParams.set('enablejsapi', '1')
    embedUrl.searchParams.set('origin', window.location.origin)
    embedUrl.searchParams.set('rel', '0')
    embedUrl.searchParams.set('modestbranding', '1')
    embedUrl.searchParams.set('iv_load_policy', '3')

    return { provider: 'youtube' as const, embedUrl: embedUrl.toString() }
  }

  if (hostname.includes('vimeo.com')) {
    let videoId = ''
    const pathParts = parsedUrl.pathname.split('/').filter(Boolean)

    if (hostname.includes('player.vimeo.com') && pathParts[0] === 'video') {
      videoId = pathParts[1] ?? ''
    } else {
      videoId = pathParts.find((part) => /^\d+$/.test(part)) ?? ''
    }

    if (!videoId) {
      return { provider: null as 'youtube' | 'vimeo' | null, embedUrl: videoUrl }
    }

    const embedUrl = new URL(`https://player.vimeo.com/video/${videoId}`)
    embedUrl.searchParams.set('title', '0')
    embedUrl.searchParams.set('byline', '0')
    embedUrl.searchParams.set('portrait', '0')
    embedUrl.searchParams.set('badge', '0')

    return { provider: 'vimeo' as const, embedUrl: embedUrl.toString() }
  }

  return { provider: null as 'youtube' | 'vimeo' | null, embedUrl: videoUrl }
}

const isSectionOpen = (section: Section, currentLessonId?: number) => {
  return section.lessons.some((lesson) => lesson.id === currentLessonId)
}

const parsedExternalVideo = computed(() => {
  return parseVideoUrl(props.currentLesson.video_url ?? '')
})

const isYoutubeVideo = computed(() => parsedExternalVideo.value.provider === 'youtube')

const isVimeoVideo = computed(() => parsedExternalVideo.value.provider === 'vimeo')

const isExternalEmbedVideo = computed(() => isYoutubeVideo.value || isVimeoVideo.value)

const isHtml5Video = computed(() => {
  const videoUrl = props.currentLesson.video_url ?? ''
  return props.currentLesson.content_type === 'video' && !!videoUrl && /\.(mp4|webm|ogg)(\?|#|$)/i.test(videoUrl)
})

const externalEmbedSrc = computed(() => parsedExternalVideo.value.embedUrl)

const isLessonCompleted = (lessonId: number) => {
  return completedLessonIds.value.has(lessonId)
}

const syncCompletedLessonIds = () => {
  const lessonIds = new Set<number>()

  props.course.sections.forEach((section) => {
    section.lessons.forEach((lesson) => {
      if (lesson.is_completed ?? lesson.completed ?? false) {
        lessonIds.add(lesson.id)
      }
    })
  })

  completedLessonIds.value = lessonIds
  currentLessonCompleted.value = lessonIds.has(props.currentLesson.id)
}

const setLessonCompleted = (lessonId: number, completed: boolean) => {
  const nextLessonIds = new Set(completedLessonIds.value)

  if (completed) {
    nextLessonIds.add(lessonId)
  } else {
    nextLessonIds.delete(lessonId)
  }

  completedLessonIds.value = nextLessonIds

  if (lessonId === props.currentLesson.id) {
    currentLessonCompleted.value = completed
  }
}

const completeLessonOnBackend = async (lessonId: number) => {
  if (!props.allowProgressUpdate) {
    setLessonCompleted(lessonId, true)
    return
  }

  await api.post(`/courses/${props.course.id}/lessons/${lessonId}/complete`)
  setLessonCompleted(lessonId, true)
}

const uncompleteLessonOnBackend = async (lessonId: number) => {
  if (!props.allowProgressUpdate) {
    setLessonCompleted(lessonId, false)
    return
  }

  await api.delete(`/courses/${props.course.id}/lessons/${lessonId}/complete`)
  setLessonCompleted(lessonId, false)
}

const markCurrentLessonCompleted = async (reason: 'video-ended' | 'article-scroll-end') => {
  if (!props.allowProgressUpdate) return
  if (currentLessonCompleted.value) return

  try {
    await completeLessonOnBackend(props.currentLesson.id)
  } catch (error) {
    console.error('[CourseLearning] Failed to mark lesson complete', error, reason)
  }
}

const handleHtml5VideoEnded = () => {
  void markCurrentLessonCompleted('video-ended')
}

const loadYoutubeApi = () => {
  if (youtubePlayerRef.value || (window as any).YT?.Player) {
    return Promise.resolve()
  }

  if (youtubeApiPromise) {
    return youtubeApiPromise
  }

  youtubeApiPromise = new Promise((resolve) => {
    const existingScript = document.querySelector('script[src="https://www.youtube.com/iframe_api"]')

    if (!existingScript) {
      const script = document.createElement('script')
      script.src = 'https://www.youtube.com/iframe_api'
      document.head.appendChild(script)
    }

    const previousReady = (window as any).onYouTubeIframeAPIReady

    ;(window as any).onYouTubeIframeAPIReady = () => {
      if (typeof previousReady === 'function') {
        previousReady()
      }

      resolve()
    }
  })

  return youtubeApiPromise
}

const destroyYoutubePlayer = () => {
  if (youtubePlayerRef.value?.destroy) {
    youtubePlayerRef.value.destroy()
  }

  youtubePlayerRef.value = null
}

const setupYoutubePlayer = async () => {
  destroyYoutubePlayer()

  if (!isYoutubeVideo.value || !videoIframeRef.value) {
    return
  }

  await loadYoutubeApi()

  const YT = (window as any).YT
  if (!YT?.Player) {
    return
  }

  youtubePlayerRef.value = new YT.Player(videoIframeRef.value, {
    events: {
      onStateChange: (event: any) => {
        if (event.data === YT.PlayerState.ENDED) {
          void markCurrentLessonCompleted('video-ended')
        }
      },
    },
  })
}

const checkArticleScrollEnd = () => {
  if (props.currentLesson.content_type !== 'article' || currentLessonCompleted.value) {
    return
  }

  const container = mainScrollRef.value
  if (!container) {
    return
  }

  const reachedBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 8
  if (reachedBottom) {
    void markCurrentLessonCompleted('article-scroll-end')
  }
}

const handleLessonCheckboxChange = async (lesson: Lesson, event: Event) => {
  const input = event.target as HTMLInputElement
  const shouldBeChecked = input.checked

  if (!props.allowProgressUpdate) {
    input.checked = isLessonCompleted(lesson.id)
    return
  }

  if (shouldBeChecked) {
    setLessonCompleted(lesson.id, true)

    try {
      await completeLessonOnBackend(lesson.id)
    } catch (error) {
      console.error('[CourseLearning] Failed to complete lesson from sidebar checkbox', error)
      setLessonCompleted(lesson.id, false)
      input.checked = false
    }

    return
  }

  const shouldUncomplete = window.confirm('Bỏ hoàn thành bài học này?')
  if (!shouldUncomplete) {
    input.checked = true
    return
  }

  setLessonCompleted(lesson.id, false)

  try {
    await uncompleteLessonOnBackend(lesson.id)
  } catch (error) {
    console.error('[CourseLearning] Failed to uncomplete lesson from sidebar checkbox', error)
    setLessonCompleted(lesson.id, true)
    input.checked = true
  }
}

const resetTracking = async () => {
  currentLessonCompleted.value = isLessonCompleted(props.currentLesson.id)
  destroyYoutubePlayer()

  await nextTick()

  if (props.currentLesson.content_type === 'article') {
    checkArticleScrollEnd()
    return
  }

  if (isYoutubeVideo.value) {
    await setupYoutubePlayer()
  }
}

watch(
  () => [props.currentLesson.id, props.currentLesson.content_type, props.currentLesson.video_url, props.currentLesson.article_content],
  () => {
    void resetTracking()
  },
  { immediate: true }
)

watch(
  () => props.course.sections,
  () => {
    syncCompletedLessonIds()
  },
  { immediate: true, deep: true }
)

onMounted(() => {
  checkArticleScrollEnd()
})

onBeforeUnmount(() => {
  destroyYoutubePlayer()
})
</script>

<template>
  <div class="learning-container">
    <div class="learning-layout">
      <main ref="mainScrollRef" class="learning-main" @scroll.passive="checkArticleScrollEnd">
        <div class="video-wrapper">
          <template v-if="currentLesson.content_type === 'video'">
            <video
              v-if="isHtml5Video"
              :src="currentLesson.video_url"
              controls
              playsinline
              @ended="handleHtml5VideoEnded"
            ></video>
            <iframe
              v-else-if="isExternalEmbedVideo"
              ref="videoIframeRef"
              :src="externalEmbedSrc"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
            ></iframe>
            <iframe v-else :src="currentLesson.video_url" allowfullscreen></iframe>
          </template>
          <div v-else class="article-content" v-html="currentLesson.article_content"></div>
        </div>
      </main>

      <aside class="learning-sidebar">
        <div class="sidebar-header">
          <span>Nội dung khóa học</span>
        </div>

        <div class="sidebar-content">
          <details
            v-for="(section, index) in course.sections"
            :key="section.id"
            class="accordion-item"
            :open="isSectionOpen(section, currentLesson.id)"
          >
            <summary class="accordion-header">
              <div class="section-title">
                <span>Section {{ index + 1 }}: {{ section.title }}</span>
                <i class="bi bi-chevron-down"></i>
              </div>
            </summary>

            <ul class="lesson-list">
              <li v-for="lesson in section.lessons" :key="lesson.id">
                <router-link
                  :to="`/courses/${course.id}/learning/${lesson.id}`"
                  class="lesson-item"
                  :class="{ active: lesson.id === currentLesson.id }"
                >
                  <input
                    type="checkbox"
                    class="lesson-checkbox"
                    :checked="isLessonCompleted(lesson.id)"
                    :disabled="!allowProgressUpdate"
                    @click.stop
                    @change="handleLessonCheckboxChange(lesson, $event)"
                  >
                  <div class="lesson-info">
                    <span class="lesson-title">{{ lesson.title }}</span>
                    <span class="lesson-time">
                      <i v-if="lesson.content_type === 'video'" class="bi bi-play-circle-fill"></i>
                      <i v-else class="bi bi-file-text-fill"></i>
                      {{ lesson.content_type === 'video' ? 'Video' : 'Bài viết' }}
                    </span>
                  </div>
                </router-link>
              </li>
            </ul>
          </details>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
:root { --dark-header: #1c1d1f; --border-color: #d1d7dc; --bg-light: #f7f9fa; --sidebar-width: 350px; }

.learning-container { height: 100vh; display: flex; flex-direction: column; }
.learning-layout { display: flex; flex: 1; overflow: hidden; }
.learning-main { flex: 1; overflow-y: auto; background: #fff; }
.video-wrapper { background: #000; width: 100%; aspect-ratio: 16/9; }
.video-wrapper video { width: 100%; height: 100%; display: block; background: #000; }
.video-wrapper iframe { width: 100%; height: 100%; border: none; }
.article-content { background: #fff; padding: 40px; line-height: 1.6; width: 100%; min-height: 100%; margin: 0 auto; }
.learning-sidebar { width: 350px; border-left: 1px solid #d1d7dc; display: flex; flex-direction: column; background: #fff; }
.sidebar-header { padding: 15px 20px; font-weight: bold; border-bottom: 1px solid #d1d7dc; }
.sidebar-content { flex: 1; overflow-y: auto; }
.accordion-item { border-bottom: 1px solid #d1d7dc; }
.accordion-header { padding: 15px 20px; background: #f7f9fa; cursor: pointer; list-style: none; }
.section-title { font-weight: bold; display: flex; justify-content: space-between; }
.lesson-list { list-style: none; }
.lesson-item { padding: 12px 20px 12px 40px; display: flex; gap: 10px; text-decoration: none; color: inherit; }
.lesson-item:hover { background: #f1f3f5; }
.lesson-item.active { background: #d1d7dc; font-weight: 600; }
.lesson-info { flex: 1; }
.lesson-title { display: block; font-size: 14px; }
.lesson-time { font-size: 12px; color: #6a6f73; }
@media (max-width: 992px) {
  .learning-layout { flex-direction: column; }
  .learning-sidebar { width: 100%; border-left: none; }
}
</style>
