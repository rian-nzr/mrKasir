<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="currencyInput">
        <x-filament::input
            :attributes="
                \Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())
                    ->merge([
                        'disabled' => $isDisabled(),
                        'id' => $getId(),
                        'inlinePrefix' => $getPrefixLabel(),
                        'inlineSuffix' => $getSuffixLabel(),
                        'placeholder' => $getPlaceholder(),
                        'required' => $isRequired() && (! $isConcealed()),
                        'type' => 'text',
                        'wire:key' => $this->generateInputKey(),
                    ], escape: false)
                    ->merge($getExtraInputAttributes(), escape: false)
                    ->merge([
                        'wire:model' => $getStatePath(),
                    ], escape: false)
                    ->class([
                        'fi-input-wrp-input',
                        'currency-input',
                    ])
            "
            x-on:input="formatCurrency($event)"
            x-on:blur="formatCurrency($event)"
        />
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('currencyInput', () => ({
                formatCurrency(event) {
                    let value = event.target.value;
                    
                    // Remove all non-digits
                    value = value.replace(/[^\d]/g, '');
                    
                    if (value) {
                        // Format with thousands separator
                        const formatted = parseInt(value).toLocaleString('id-ID');
                        event.target.value = formatted;
                        
                        // Trigger wire model update
                        this.$wire.set(event.target.getAttribute('wire:model'), formatted);
                    }
                }
            }));
        });
    </script>
</x-dynamic-component>
