<template>   
    <field-wrapper class="text-center" v-if="field.locales.length > 1">  
        <p  
            v-for="locale in field.locales" 
            class="remove-last-margin-bottom leading-normal w-full py-4 px-8 text-center cursor-pointer"  
            :class="locale.name == activeLocale ? 'text-success font-bold bg-success-light' : 'text-primary bg-10'"
            @click.prevent="setActiveLocale(locale.name)"
        >{{ locale.label }}</p> 
    </field-wrapper>
</template>

<script>
import { FormField } from 'laravel-nova'

export default {
    mixins: [FormField],  
    data() {
        return {
            activeLocale  : {
                type: String, required: true
            },
            defaultLocale : 'fa',
        }
    },
    mounted() {  
        this.setActiveLocale(this.field.activeLocale || this.defaultLocale);  

        Nova.$on('locale.changed', (locale) => {this.activeLocale = locale})   
    },  
    methods: { 
        setActiveLocale(locale) {
            if(this.activeLocale === locale) return;  

            this.activeLocale = locale;

            this.$parent.$children.map(function(component) {
                if(component.field == undefined) return;
                 
                if(component.field.locale == locale) { 
                    component.$el.classList.remove('force-hidden') 
                } else if(component.field.locale) { 
                    component.$el.classList.add('force-hidden')
                } 
            })

            Nova.$emit('locale.changed', locale)  
        }, 
    } 
}
</script>
<style>
.force-hidden {
    display: none !important;
} 
</style>