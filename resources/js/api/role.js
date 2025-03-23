import request from '@/utils/request';
import Resource from '@/api/resource';

class RoleResource extends Resource {
  constructor() {
    super('roles');
  }

  permissions(id) {
    return request({
      url: `/${this.uri}/${id}/permissions`,
      method: 'get',
    });
  }

  updatePermissions(id, permissions) {
    return request({
      url: `/${this.uri}/${id}`,
      method: 'put',
      data: { permissions },
    });
  }
}

export default new RoleResource();
