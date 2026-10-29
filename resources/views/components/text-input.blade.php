@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#EA4335] focus:ring-[#EA4335] rounded-md shadow-sm']) }}>
