import { Component } from '@angular/core';
import { FileService } from '../../../services/file';
import { DownloadComponent } from '../../icons/download/download.component';
import { CheckComponent } from '../../icons/check/check.component';
import { CopyComponent } from '../../icons/copy/copy.component';
import { CircleCheckComponent } from '../../icons/circle-check/circle-check.component';
import { FileTextComponent } from '../../icons/file-text/file-text.component';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [DownloadComponent, CheckComponent, CircleCheckComponent, CopyComponent, FileTextComponent],
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss'
})
export class HomeComponent {
  inProgress: boolean = false;
  previewUrl: string = '';
  isDragging = false;
  file: File | null = null
  icon: 'copy' | 'check' = 'copy';

  constructor(private fileService: FileService) { }

  // Quando o arquivo é arrastado sobre a área
  onDragOver(event: DragEvent): void {
    event.preventDefault();
    this.isDragging = true;
  }

  // Quando o arquivo é arrastado para fora da área
  onDragLeave(event: DragEvent): void {
    event.preventDefault();
    this.isDragging = false;
  }

  // Quando o arquivo é solto na área
  onDrop(event: DragEvent): void {
    event.preventDefault();
    this.isDragging = false;

    if (event.dataTransfer && event.dataTransfer.files.length > 0) {
      this.file = event.dataTransfer.files[0];
      this.uploadFile()
    }
  }

  // Quando o arquivo é selecionado via input
  onFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;

    if (input.files && input.files.length > 0) {
      this.file = input.files[0];
      this.uploadFile()
    }
  }

  uploadFile()
  {
    this.inProgress = true;

    if (this.file) {
      const file = this.file
      const name = file.name;
      const type = file.type;
      const size = file.size;

      this.fileService.getUploadUrl(name, type, size).subscribe(
        (data) => {
          const signedUrl = data.signedUrl
          const fileId = data.file

          this.fileService.uploadFile(file, signedUrl).subscribe({
            error: (e) => console.error('Error ao realizar upload-storage:', e),
            complete: () => {
                this.previewUrl = this.fileService.getPreviewUrl(fileId)
                this.inProgress = false;
            }
          })
        },
        (error) => {
          console.error('Error ao realizar upload:', error)
        }
      )
    }
  }

  copyToClipboard(): void {
    navigator.clipboard.writeText(this.previewUrl).then(
      () => {
        this.icon = 'check';

        setTimeout(() => {
          this.icon = 'copy';
        }, 3000);
      },
      (err) => {
        console.error('Erro ao copiar texto:', err);
      }
    );
  }
}
