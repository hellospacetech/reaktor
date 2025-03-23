<script setup lang="ts">
import { ref, onBeforeUnmount, onMounted, computed } from 'vue';
import { useFloating, offset, autoPlacement, arrow, shift } from '@floating-ui/vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
  position?: 'top' | 'right' | 'bottom' | 'left';
  maxWidth?: string;
  arrowSize?: number;
  class?: string;
}>();

const arrowRef = ref<HTMLElement | null>(null);
const defaultPosition = ref<'top' | 'right' | 'bottom' | 'left'>(props.position || 'top');
const isOpen = ref(false);
const floating = ref<HTMLElement | null>(null);
const reference = ref<HTMLElement | null>(null);

// Compute the middleware based on position
const middleware = computed(() => {
  const middlewares = [
    offset(8),
    shift({ padding: 8 }),
  ];
  
  if (props.position) {
    // İstek üzerine belirli bir konum kullanılıyorsa
    middlewares.push(arrow({ element: arrowRef }));
  } else {
    // Konumu otomatik olarak belirle
    middlewares.push(
      autoPlacement({
        allowedPlacements: ['top', 'right', 'bottom', 'left'],
      }),
      arrow({ element: arrowRef })
    );
  }
  
  return middlewares;
});

// Floating UI entegrasyonu
const { floatingStyles, placement, middlewareData } = useFloating(
  reference,
  floating,
  {
    placement: defaultPosition.value,
    middleware: middleware.value,
    whileElementsMounted: (reference, floating, update) => {
      const cleanup = () => {
        window.removeEventListener('scroll', update);
        window.removeEventListener('resize', update);
      };
      
      window.addEventListener('scroll', update);
      window.addEventListener('resize', update);
      
      // İlk güncellemeyi yap
      update();
      
      // Temizleme fonksiyonu
      return cleanup;
    },
  }
);

// Arrow pozisyonunu hesapla
const arrowPosition = computed(() => {
  if (!middlewareData.value.arrow) {
    return {};
  }

  const { x, y } = middlewareData.value.arrow;
  const placementValue = placement.value.split('-')[0] as string;
  const staticSideMap: Record<string, string> = {
    top: 'bottom',
    right: 'left',
    bottom: 'top',
    left: 'right',
  };
  const staticSide = staticSideMap[placementValue];

  return {
    left: x != null ? `${x}px` : '',
    top: y != null ? `${y}px` : '',
    [staticSide]: `-${props.arrowSize || 5}px`,
  };
});

// Mouse olayları
const showTooltip = () => {
  isOpen.value = true;
};

const hideTooltip = () => {
  isOpen.value = false;
};

// Temizlik
onBeforeUnmount(() => {
  isOpen.value = false;
});

// Reference div'ini doldur
onMounted(() => {
  if (reference.value && reference.value.firstElementChild) {
    const observer = new MutationObserver(() => {
      if (reference.value && reference.value.firstElementChild && floating.value) {
        floating.value.style.minWidth = `${reference.value.clientWidth}px`;
      }
    });
    
    observer.observe(reference.value, { childList: true, subtree: true });
    
    return () => observer.disconnect();
  }
});

const model = defineModel();
</script>

<template>
  <div 
    ref="reference" 
    class="inline-block" 
    @mouseenter="showTooltip"
    @mouseleave="hideTooltip"
    @focus="showTooltip"
    @blur="hideTooltip"
  >
    <slot />
    
    <Teleport to="body">
      <div
        v-if="isOpen"
        ref="floating"
        :style="floatingStyles"
        :class="twMerge(
          'bg-gray-900 text-white text-sm rounded-md px-3 py-2 shadow-md z-50',
          props.maxWidth ? `max-w-[${props.maxWidth}]` : 'max-w-xs',
          props.class
        )"
      >
        <div 
          ref="arrowRef" 
          :style="arrowPosition"
          class="absolute w-0 h-0 border-solid"
          :class="{
            'border-t-gray-900 border-x-transparent border-b-0 border-x-[5px] border-t-[5px]': placement.includes('bottom'),
            'border-b-gray-900 border-x-transparent border-t-0 border-x-[5px] border-b-[5px]': placement.includes('top'),
            'border-l-gray-900 border-y-transparent border-r-0 border-y-[5px] border-l-[5px]': placement.includes('right'),
            'border-r-gray-900 border-y-transparent border-l-0 border-y-[5px] border-r-[5px]': placement.includes('left')
          }"
        />
        <slot name="content" />
      </div>
    </Teleport>
  </div>
</template> 