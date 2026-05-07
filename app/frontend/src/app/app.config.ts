import { ApplicationConfig } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import {
  LUCIDE_ICONS,
  LucideIconProvider,
  ArrowLeft,
  CheckCircle2,
  ChevronDown,
  ChevronRight,
  Circle,
  CircleDashed,
  Clock,
  LogOut,
  Pencil,
  Plus,
  Trash2,
  X
} from 'lucide-angular';
import { routes } from './app.routes';
import { authInterceptor } from './core/interceptors/auth.interceptor';

export const appConfig: ApplicationConfig = {
  providers: [
    provideRouter(routes),
    provideHttpClient(withInterceptors([authInterceptor])),
    {
      provide: LUCIDE_ICONS,
      multi: true,
      useValue: new LucideIconProvider({
        ArrowLeft,
        CheckCircle2,
        ChevronDown,
        ChevronRight,
        Circle,
        CircleDashed,
        Clock,
        LogOut,
        Pencil,
        Plus,
        Trash2,
        X
      })
    }
  ]
};