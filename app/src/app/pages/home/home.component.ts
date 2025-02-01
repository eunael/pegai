import { Component } from '@angular/core';
import { FileService } from '../../../services/file';
import { DownloadComponent } from '../../icons/download/download.component';
import { CheckComponent } from '../../icons/check/check.component';
import { CopyComponent } from '../../icons/copy/copy.component';
import { CircleCheckComponent } from '../../icons/circle-check/circle-check.component';
import { FileTextComponent } from '../../icons/file-text/file-text.component';
import { z } from 'zod';

const fileSchema = z.object({
  name: z
    .string()
    .nonempty("O nome do arquivo é obrigatório")
    .max(255, "O nome do arquivo deve ter no máximo 255 caracteres"),
  size: z
    .number()
    .min(1, "O tamanho do arquivo é obrigatório")
    .max(200 * 1024 * 1024, "O tamanho do arquivo não pode exceder 200MB"), // Convertendo 200MB para bytes
  type: z
    .string()
    .regex(/\w+\/[-+.\w]+/, "O tipo do arquivo deve seguir o padrão MIME"),
});

const invalidMimeTypes = ['application/x-msdownload', 'application/x-msdos-program', 'application/x-shellscript', 'application/x-sh', 'application/x-cgi', 'application/java-archive', 'application/x-executable'];

const validateFile = (file: { name: string; size: number; type: string }) => {
  const validationResult = fileSchema.safeParse(file);
  if (!validationResult.success) {
    throw new Error(
      validationResult.error.errors.map((err) => err.message).join(", ")
    );
  }

  // Validar o mime type
  if (invalidMimeTypes.includes(file.type)) {
    throw new Error("O tipo MIME do arquivo não é permitido");
  }

  return true;
};

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
  msgError: string = '';

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
    this.msgError = ''
    this.inProgress = true;

    if (this.file) {
      const file = this.file
      const name = file.name;
      const type = file.type;
      const size = file.size;

      // try {
      //   validateFile({
      //     name,
      //     size,
      //     type,
      //   });
      // } catch (err: any) {
      //   this.msgError = err.message
      // }

      this.fileService.getUploadUrl(name, type, size).subscribe({
        next: (data) => {
          const signedUrl = data.signedUrl
          const fileId = data.file

          this.fileService.uploadFile(file, signedUrl).subscribe({
            error: (e) => {this.msgError = 'Error when perfoming upload-storage', this.inProgress = false; this.file = null},
            complete: () => {
                this.previewUrl = this.fileService.getPreviewUrl(fileId)
                this.inProgress = false;
            }
          })
        },
        error: (e) => {this.msgError = e.error.message; this.inProgress = false; this.file = null}
      })
    }
  }

  copyToClipboard(): void {
    this.msgError = ''

    navigator.clipboard.writeText(this.previewUrl).then(
      () => {
        this.icon = 'check';

        setTimeout(() => {
          this.icon = 'copy';
        }, 3000);
      },
      (err) => {
        this.msgError = 'Erro ao copiar texto';
      }
    );
  }
}
