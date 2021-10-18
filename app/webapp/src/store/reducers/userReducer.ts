const initialState = {
  loading: false,
  error: null,
  profile: {
    id: 1,
    first_name: 'Hello',
    last_name: 'Student'
  }
};

export const userReducer = (state = initialState, action: any) => {
  switch (action.type) {
    case 'SET_USER_LOADING':
      return {
        ...state,
        loading: true
      }
    case 'SET_USER_ERROR':
      return {
        ...state,
        loading: false,
        error: action.payload
      }
    case 'SET_USER_SUCCESS':
      return {
        ...state,
        loading: false,
        profile: action.payload
      }
    case 'PUT_USER_SUCCESS':
      return {
        ...state,
        loading: false,
        profile: {...state.profile, ...action.payload}
      }
    default:
      return state;
  }
}
