import { ref, computed } from 'vue'
import type { AgeGroup, ToyCategory, TcgSubCategory, ToyProduct } from '@/shared/types/toy.types'
import { MOCK_TOYS_DATA } from '@/shared/constants/mock-toys.data'

export const useToyFinderComposable = () => {
  const isOpen = ref(false)
  const currentStep = ref(1) // 1: Age, 2: Interest/TCG, 3: Budget, 4: Results

  const selectedAge = ref<AgeGroup | null>(null)
  const selectedCategory = ref<ToyCategory | null>(null)
  const selectedTcgSeries = ref<TcgSubCategory | null>(null)
  const selectedBudget = ref<number>(60)

  const openFinder = () => {
    isOpen.value = true
    currentStep.value = 1
  }

  const closeFinder = () => {
    isOpen.value = false
  }

  const selectAge = (age: AgeGroup) => {
    selectedAge.value = age
    currentStep.value = 2
  }

  const selectInterest = (cat: ToyCategory, series?: TcgSubCategory) => {
    selectedCategory.value = cat
    selectedTcgSeries.value = series || null
    currentStep.value = 3
  }

  const submitBudget = (budget: number) => {
    selectedBudget.value = budget
    currentStep.value = 4
  }

  const recommendedToys = computed<ToyProduct[]>(() => {
    const list = MOCK_TOYS_DATA.filter((toy) => {
      // If TCG series was selected, prioritize it directly
      if (selectedTcgSeries.value && toy.tcgSeries === selectedTcgSeries.value) {
        return true
      }
      // If category was selected
      if (selectedCategory.value && toy.category === selectedCategory.value) {
        return true
      }
      // Age and budget fallback
      const ageMatches = !selectedAge.value || toy.ageGroup === selectedAge.value
      const budgetMatches = toy.price <= selectedBudget.value + 15
      return ageMatches && budgetMatches
    })

    return list.slice(0, 3)
  })

  const resetFinder = () => {
    selectedAge.value = null
    selectedCategory.value = null
    selectedTcgSeries.value = null
    selectedBudget.value = 60
    currentStep.value = 1
  }

  return {
    isOpen,
    currentStep,
    selectedAge,
    selectedCategory,
    selectedTcgSeries,
    selectedBudget,
    recommendedToys,

    openFinder,
    closeFinder,
    selectAge,
    selectInterest,
    submitBudget,
    resetFinder,
  }
}
