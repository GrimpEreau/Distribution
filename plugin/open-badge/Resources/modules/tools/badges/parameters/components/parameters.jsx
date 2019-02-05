import React from 'react'

import {trans} from '#/main/app/intl/translation'
import {URL_BUTTON} from '#/main/app/buttons'
import {FormData} from '#/main/app/content/form/containers/data'


const ParametersForm = () =>
  <FormData
    level={2}
    title={trans('home')}
    name="parameters"
    target={['apiv2_parameters_update']}
    buttons={true}
    cancel={{
      type: URL_BUTTON,
      target: '#/'
    }}
    sections={[
      {
        title: trans('general'),
        fields: [
          {
            name: 'props.badges.enable_default',
            type: 'boolean',
            label: trans('required_validation')
          }
        ]
      }
    ]}
  />

export {
  ParametersForm
}