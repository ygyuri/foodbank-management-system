// stores/abilityStore.js
import { defineStore } from 'pinia';
import { Ability, AbilityBuilder } from '@casl/ability';
import { defineAbilitiesFor } from '@/utils/ability';

export const useAbilityStore = defineStore('ability', {
  state: () => ({
    roles: [], // Current roles
    permissions: [], // Current permissions
    ability: new Ability([]), // CASL ability instance
  }),
  actions: {
    // Initialize abilities based on roles and permissions
    initializeAbilities(roles, permissions) {
      this.roles = roles;
      this.permissions = permissions;
      this.ability.update(defineAbilitiesFor(roles, permissions).rules);
    },

    // Update abilities dynamically when roles or permissions change
    updateAbilities(roles, permissions) {
      this.roles = roles;
      this.permissions = permissions;
      this.ability.update(defineAbilitiesFor(roles, permissions).rules);
    },

    // Check if a specific action is allowed for a subject
    can(action, subject) {
      return this.ability.can(action, subject);
    },

    // Toggle a permission dynamically
    togglePermission(action, subject, allowed) {
      const rules = this.ability.rules;
      if (allowed) {
        this.ability.update([...rules, { action, subject }]);
      } else {
        this.ability.update(rules.filter(rule => !(rule.action === action && rule.subject === subject)));
      }
    },
  },
});