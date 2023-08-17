import {App} from "vue";
import Oruga from "@oruga-ui/oruga-next";
import {bootstrapConfig} from "@oruga-ui/theme-bootstrap";
import OrugaIcon from "~/components/Common/OrugaIcon.vue";

export default function installOruga(vueApp: App): void {
    vueApp.use(Oruga, {
        ...bootstrapConfig,
        iconPack: 'materialIconsFont',
        iconComponent: OrugaIcon,
        icon: {
            override: true
        },
        customIconPacks: {
            materialIconsFont: {
                sizes: {
                    default: '',
                    small: 'sm',
                    medium: '',
                    large: 'lg',
                },
                internalIcons: {
                    check: 'check',
                    information: 'info',
                    alert: 'warning',
                    'alert-circle': 'warning',
                    'arrow-up': 'arrow_drop_up',
                    'chevron-right': 'arrow_right',
                    'chevron-left': 'arrow_left',
                    'chevron-down': 'arrow_down',
                    eye: 'visibility',
                    'eye-off': 'visibility_off',
                    'caret-down': 'expand_more',
                    'caret-up': 'expand_less',
                    loading: 'sync',
                    times: 'close',
                    'close-circle': 'close',
                }
            }
        },
        modal: {
            ...bootstrapConfig.modal,
            contentClass: "modal-dialog",
        },
        pagination: {
            ...bootstrapConfig.pagination,
            orderClass: '',
        },
        tabs: {
            ...bootstrapConfig.tabs,
            animated: false
        },
        notification: {
            ...bootstrapConfig.notification,
            rootClass: (_, {props}) => {
                const classes = ['alert', 'notification'];
                if (props.variant)
                    classes.push(`text-bg-${props.variant}`);
                return classes.join(' ');
            },
        }
    });
}
